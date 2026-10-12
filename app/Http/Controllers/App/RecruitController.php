<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\ContactUnlock;
use App\Models\ProfessionalProfile;
use App\Models\SavedSearch;
use App\Models\Shortlist;
use App\Models\ShortlistItem;
use App\Notifications\PlatformNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecruitController extends Controller
{
    protected function org(Request $request)
    {
        return $request->user()->organization();
    }

    public function unlock(Request $request, ProfessionalProfile $profile)
    {
        $org = $this->org($request);
        abort_unless($profile->isDiscoverable(), 404);
        if (! $profile->allow_direct_contact) {
            return back()->with('error', 'This professional prefers contact through '.setting('site_name').' — send a contact request instead.');
        }
        if ($org->hasUnlocked($profile)) {
            return back()->with('info', 'Already unlocked.');
        }

        $result = DB::transaction(function () use ($org, $request, $profile) {
            $org = $org->newQuery()->lockForUpdate()->find($org->id);
            $sub = $org->activeSubscription();
            if ($sub && $sub->unlocks_remaining > 0) {
                $sub->decrement('unlocks_remaining');
                $source = 'subscription';
            } elseif ($org->unlock_credits > 0) {
                $org->forceFill(['unlock_credits' => $org->unlock_credits - 1])->save();
                $source = 'credit';
            } else {
                return null;
            }

            return ContactUnlock::create(['organization_id' => $org->id, 'user_id' => $request->user()->id, 'professional_profile_id' => $profile->id, 'source' => $source]);
        });

        if (! $result) {
            return redirect()->route('app.billing')->with('info', 'You’re out of contact unlocks. Grab a plan or a few credits to keep going.');
        }

        AuditLog::record('contact.unlocked', $profile, ['organization' => $org->id, 'source' => $result->source]);
        $profile->user->notify(new PlatformNotification('Someone’s interested 👀', $org->name.' unlocked your contact details.', route('app.dashboard'), 'eye'));

        return back()->with('success', 'Unlocked! Contact details are now visible to your team.');
    }

    public function cv(Request $request, ProfessionalProfile $profile)
    {
        abort_unless($this->org($request)->hasUnlocked($profile) && $profile->allow_direct_contact && $profile->cv_path && Storage::disk('local')->exists($profile->cv_path), 404);
        AuditLog::record('contact.cv_downloaded', $profile);

        return Storage::disk('local')->download($profile->cv_path, 'CV-'.$profile->slug.'.'.pathinfo($profile->cv_path, PATHINFO_EXTENSION));
    }

    public function contactRequest(Request $request, ProfessionalProfile $profile)
    {
        abort_unless($profile->isDiscoverable(), 404);
        $data = $request->validate(['subject' => 'required|string|max:150', 'message' => 'required|string|min:20|max:2000']);
        $org = $this->org($request);

        $recent = ContactRequest::where('organization_id', $org->id)->where('professional_profile_id', $profile->id)->where('created_at', '>', now()->subDays(7))->exists();
        if ($recent) {
            return back()->with('info', 'You already sent a request this week. Give them a moment 🙏');
        }
        ContactRequest::create(['organization_id' => $org->id, 'sender_id' => $request->user()->id, 'professional_profile_id' => $profile->id] + $data);
        $profile->user->notify(new PlatformNotification('New contact request 📩', $org->name.': '.$data['subject'], route('app.requests.index'), 'inbox'));

        return back()->with('success', 'Request sent. If they accept, a chat opens in Messages.');
    }

    public function addToShortlist(Request $request, ProfessionalProfile $profile)
    {
        $data = $request->validate(['shortlist_id' => 'required|integer']);
        $list = $this->org($request)->shortlists()->findOrFail($data['shortlist_id']);
        ShortlistItem::firstOrCreate(['shortlist_id' => $list->id, 'professional_profile_id' => $profile->id]);

        return back()->with('success', $profile->user->firstName().' added to “'.$list->name.'”.');
    }

    public function shortlists(Request $request)
    {
        return view('app.hirer.shortlists', ['lists' => $this->org($request)->shortlists()->withCount('items')->with('creator')->latest()->get()]);
    }

    public function createShortlist(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:80', 'description' => 'nullable|string|max:300']);
        $list = $this->org($request)->shortlists()->create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('app.shortlists.show', $list)->with('success', 'Shortlist created. Add people from search.');
    }

    public function shortlist(Request $request, Shortlist $shortlist)
    {
        $this->own($request, $shortlist);

        return view('app.hirer.shortlist', [
            'list' => $shortlist,
            'items' => $shortlist->items()->with('profile.user', 'profile.skills')->latest()->get(),
            'org' => $this->org($request),
        ]);
    }

    public function updateItem(Request $request, Shortlist $shortlist, ShortlistItem $item)
    {
        $this->own($request, $shortlist);
        abort_unless($item->shortlist_id === $shortlist->id, 404);
        $item->update($request->validate(['note' => 'nullable|string|max:2000', 'rating' => 'nullable|integer|between:1,5']));

        return back()->with('success', 'Notes saved.');
    }

    public function removeItem(Request $request, Shortlist $shortlist, ShortlistItem $item)
    {
        $this->own($request, $shortlist);
        abort_unless($item->shortlist_id === $shortlist->id, 404);
        $item->delete();

        return back()->with('success', 'Removed from shortlist.');
    }

    public function deleteShortlist(Request $request, Shortlist $shortlist)
    {
        $this->own($request, $shortlist);
        $shortlist->delete();

        return redirect()->route('app.shortlists.index')->with('success', 'Shortlist deleted.');
    }

    /** CSV export of permitted, non-private information only. */
    public function export(Request $request, Shortlist $shortlist)
    {
        $this->own($request, $shortlist);
        $org = $this->org($request);
        $items = $shortlist->items()->with('profile.user', 'profile.skills')->get();
        AuditLog::record('shortlist.exported', $shortlist);

        return response()->streamDownload(function () use ($items, $org) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Role', 'Location', 'Years', 'Availability', 'Verified skills', 'Profile URL', 'Contact email', 'Contact phone', 'Rating', 'Notes']);
            foreach ($items as $item) {
                $p = $item->profile;
                $unlocked = $org->hasUnlocked($p) && $p->allow_direct_contact;
                fputcsv($out, [
                    $p->user->name, $p->primary_role, $p->location(), $p->years_experience, $p->availabilityLabel(),
                    $p->verifiedSkills()->map(fn ($s) => $s->name.' L'.$s->pivot->verified_level)->implode('; '),
                    route('talent.show', $p), $unlocked ? $p->contact_email : '(locked)', $unlocked ? $p->contact_phone : '(locked)', $item->rating, $item->note,
                ]);
            }
            fclose($out);
        }, str($shortlist->name)->slug().'.csv', ['Content-Type' => 'text/csv']);
    }

    public function compare(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))->map(fn ($i) => (int) $i)->filter()->take(4);
        $profiles = ProfessionalProfile::discoverable()->whereIn('id', $ids)->with('user', 'skills')->get();
        $skills = $profiles->flatMap(fn ($p) => $p->skills)->unique('id')->sortBy('name');

        return view('app.hirer.compare', ['profiles' => $profiles, 'skills' => $skills, 'org' => $this->org($request)]);
    }

    public function unlocks(Request $request)
    {
        return view('app.hirer.unlocks', [
            'unlocks' => $this->org($request)->unlocks()->with('profile.user', 'user')->latest()->paginate(20),
            'org' => $this->org($request),
        ]);
    }

    public function saveSearch(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:80', 'filters' => 'required|string|max:2000']);
        $filters = array_filter((array) json_decode($data['filters'], true), fn ($v) => $v !== null && $v !== '');
        SavedSearch::create(['user_id' => $request->user()->id, 'name' => $data['name'], 'filters' => $filters]);

        return back()->with('success', 'Search saved 🔖');
    }

    public function deleteSearch(Request $request, SavedSearch $search)
    {
        abort_unless($search->user_id === $request->user()->id, 403);
        $search->delete();

        return back()->with('success', 'Saved search removed.');
    }

    protected function own(Request $request, Shortlist $shortlist): void
    {
        abort_unless($shortlist->organization_id === $this->org($request)->id, 403);
    }
}
