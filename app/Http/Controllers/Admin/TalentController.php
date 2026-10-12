<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProfessionalProfile;
use App\Notifications\PlatformNotification;
use App\Support\Labels;
use Illuminate\Http\Request;

class TalentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'queue');
        $profiles = ProfessionalProfile::with('user')->withCount(['profileSkills as verified_count' => fn ($q) => $q->whereNotNull('verified_level'), 'portfolio', 'experiences'])
            ->when($status === 'queue', fn ($q) => $q->whereIn('status', ['under_review', 'conditionally_approved']))
            ->when($status !== 'queue' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->q, fn ($q, $t) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")))
            ->orderByRaw('submitted_at is null')->orderBy('submitted_at')->paginate(25)->withQueryString();

        return view('admin.talent.index', ['profiles' => $profiles, 'status' => $status, 'statuses' => Labels::PROFILE_STATUS]);
    }

    public function show(ProfessionalProfile $profile)
    {
        return view('admin.talent.show', [
            'profile' => $profile->load(['user', 'profileSkills.skill', 'experiences', 'portfolio', 'certifications']),
            'attempts' => $profile->user->attempts()->with('assessment.skill')->latest()->get(),
            'audit' => AuditLog::where('subject_type', $profile->getMorphClass())->where('subject_id', $profile->id)->with('user')->latest('id')->take(15)->get(),
        ]);
    }

    public function decide(Request $request, ProfessionalProfile $profile)
    {
        $data = $request->validate([
            'decision' => 'required|in:approve,conditional,changes,suspend,reinstate',
            'notes' => 'nullable|string|max:2000',
        ]);
        if (in_array($data['decision'], ['changes', 'suspend'], true) && blank($data['notes'])) {
            return back()->with('error', 'Add a note explaining what needs to change — the professional will see it.');
        }

        $status = ['approve' => 'approved', 'conditional' => 'conditionally_approved', 'changes' => 'draft', 'suspend' => 'suspended', 'reinstate' => 'approved'][$data['decision']];
        $profile->forceFill([
            'status' => $status,
            'review_notes' => $data['notes'],
            'approved_at' => $status === 'approved' ? ($profile->approved_at ?? now()) : $profile->approved_at,
        ])->save();
        AuditLog::record('profile.'.$data['decision'], $profile, ['notes' => $data['notes']]);

        $messages = [
            'approve' => ['You’re approved 🎉', 'Your profile is fully approved and live in search.'],
            'conditional' => ['You’re live (conditionally) 🚀', 'Your profile is visible while we finish reviewing the rest.'],
            'changes' => ['A few changes needed ✏️', 'Our reviewers left notes on your profile. Update it and resubmit.'],
            'suspend' => ['Profile suspended', 'Your profile has been suspended. Check the notes on your dashboard and contact us to appeal.'],
            'reinstate' => ['Welcome back ✅', 'Your profile has been reinstated.'],
        ][$data['decision']];
        $profile->user->notify(new PlatformNotification($messages[0], $messages[1], route('app.dashboard'), 'user-check'));

        return redirect()->route('admin.talent.index')->with('success', 'Decision saved: '.Labels::PROFILE_STATUS[$status].'.');
    }

    public function verify(Request $request, ProfessionalProfile $profile)
    {
        $data = $request->validate(['what' => 'required|in:identity,portfolio,certification,portfolio_item', 'id' => 'nullable|integer', 'undo' => 'nullable|boolean']);
        $value = $request->boolean('undo') ? null : now();

        match ($data['what']) {
            'identity' => $profile->forceFill(['identity_verified_at' => $value])->save(),
            'portfolio' => $profile->forceFill(['portfolio_verified_at' => $value])->save(),
            'certification' => $profile->certifications()->findOrFail($data['id'])->forceFill(['verified_at' => $value])->save(),
            'portfolio_item' => $profile->portfolio()->findOrFail($data['id'])->forceFill(['verified_at' => $value])->save(),
        };
        AuditLog::record('profile.verify_'.$data['what'], $profile, ['id' => $data['id'] ?? null, 'undo' => $request->boolean('undo')]);

        return back()->with('success', 'Verification updated.');
    }
}
