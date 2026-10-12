<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certification;
use App\Models\Experience;
use App\Models\PortfolioItem;
use App\Models\ProfileSkill;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    protected function profile(Request $request)
    {
        return $request->user()->profile;
    }

    public function edit(Request $request)
    {
        return view('app.talent.profile.edit', ['profile' => $this->profile($request), 'user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'headline' => 'nullable|string|max:160',
            'primary_role' => 'nullable|string|max:80',
            'bio' => 'nullable|string|max:3000',
            'city' => 'nullable|string|max:80',
            'country' => 'required|string|max:80',
            'timezone' => 'required|timezone',
            'languages' => 'nullable|string|max:150',
            'availability' => 'required|in:'.implode(',', array_keys(Labels::AVAILABILITY)),
            'work_arrangement' => 'required|in:'.implode(',', array_keys(Labels::ARRANGEMENTS)),
            'years_experience' => 'required|integer|min:0|max:50',
            'currency' => 'required|in:'.implode(',', array_keys(Labels::CURRENCIES)),
            'rate_min' => 'nullable|string|max:20',
            'rate_max' => 'nullable|string|max:20',
            'rate_unit' => 'required|in:hour,month,project',
            'show_rate' => 'nullable|boolean',
            'github_url' => 'nullable|url|max:255',
            'gitlab_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'behance_url' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:40',
            'allow_direct_contact' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'avatar' => 'nullable|image|max:2048',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = $request->user();
        $user->name = $data['name'];
        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }
        $user->save();

        if ($request->hasFile('cv')) {
            if ($profile->cv_path) {
                Storage::disk('local')->delete($profile->cv_path);
            }
            $profile->cv_path = $request->file('cv')->store('cvs', 'local');
        }

        $profile->fill(collect($data)->except(['name', 'avatar', 'cv', 'rate_min', 'rate_max'])->all());
        $profile->rate_min = to_minor($data['rate_min'] ?? null);
        $profile->rate_max = to_minor($data['rate_max'] ?? null);
        $profile->show_rate = $request->boolean('show_rate');
        $profile->allow_direct_contact = $request->boolean('allow_direct_contact');
        $profile->is_public = $request->boolean('is_public');
        $profile->save();

        return back()->with('success', 'Profile saved ✨');
    }

    public function cv(Request $request)
    {
        $profile = $this->profile($request);
        abort_unless($profile->cv_path && Storage::disk('local')->exists($profile->cv_path), 404);

        return Storage::disk('local')->download($profile->cv_path, 'CV-'.$profile->slug.'.'.pathinfo($profile->cv_path, PATHINFO_EXTENSION));
    }

    public function skills(Request $request)
    {
        $profile = $this->profile($request)->load('profileSkills.skill.assessments');

        return view('app.talent.profile.skills', [
            'profile' => $profile,
            'categories' => SkillCategory::with(['skills' => fn ($q) => $q->where('is_active', true)])->orderBy('position')->get(),
            'claimed' => $profile->profileSkills->sortByDesc('is_primary'),
        ]);
    }

    public function addSkill(Request $request)
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'skill_id' => 'required|exists:skills,id',
            'self_level' => 'required|integer|between:1,5',
            'years' => 'required|integer|between:0,40',
        ]);
        abort_if($profile->profileSkills()->count() >= 25, 422, 'You can claim up to 25 skills.');

        $ps = ProfileSkill::firstOrNew(['professional_profile_id' => $profile->id, 'skill_id' => $data['skill_id']]);
        $ps->fill(['self_level' => $data['self_level'], 'years' => $data['years']]);
        if (! $profile->profileSkills()->where('is_primary', true)->exists()) {
            $ps->is_primary = true;
        }
        $ps->save();

        return back()->with('success', Skill::find($data['skill_id'])->name.' added. Verify it to get the badge ✅');
    }

    public function updateSkill(Request $request, ProfileSkill $profileSkill)
    {
        abort_unless($profileSkill->professional_profile_id === $this->profile($request)->id, 403);
        $data = $request->validate([
            'self_level' => 'nullable|integer|between:1,5',
            'years' => 'nullable|integer|between:0,40',
            'is_primary' => 'nullable|boolean',
        ]);
        if ($request->boolean('is_primary')) {
            ProfileSkill::where('professional_profile_id', $profileSkill->professional_profile_id)->update(['is_primary' => false]);
            $profileSkill->is_primary = true;
        }
        // Self-reported fields only; verified_level can only be set by passing an assessment.
        $profileSkill->fill(array_filter(['self_level' => $data['self_level'] ?? null, 'years' => $data['years'] ?? null], fn ($v) => $v !== null))->save();

        return back()->with('success', 'Skill updated.');
    }

    public function removeSkill(Request $request, ProfileSkill $profileSkill)
    {
        abort_unless($profileSkill->professional_profile_id === $this->profile($request)->id, 403);
        $profileSkill->delete();

        return back()->with('success', 'Skill removed.');
    }

    public function experience(Request $request)
    {
        $profile = $this->profile($request)->load('experiences', 'certifications');

        return view('app.talent.profile.experience', compact('profile'));
    }

    public function saveExperience(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'kind' => 'required|in:work,education',
            'title' => 'required|string|max:120',
            'organization' => 'required|string|max:120',
            'location' => 'nullable|string|max:120',
            'started_on' => 'nullable|date',
            'ended_on' => 'nullable|date|after_or_equal:started_on',
            'is_current' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
        ]);
        $profile = $this->profile($request);
        $exp = isset($data['id']) ? $profile->experiences()->findOrFail($data['id']) : new Experience;
        $exp->fill(collect($data)->except('id')->all());
        $exp->is_current = $request->boolean('is_current');
        if ($exp->is_current) {
            $exp->ended_on = null;
        }
        $exp->professional_profile_id = $profile->id;
        $exp->save();

        return back()->with('success', 'Saved.');
    }

    public function deleteExperience(Request $request, Experience $experience)
    {
        abort_unless($experience->professional_profile_id === $this->profile($request)->id, 403);
        $experience->delete();

        return back()->with('success', 'Removed.');
    }

    public function saveCertification(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'issuer' => 'required|string|max:120',
            'issued_on' => 'nullable|date',
            'expires_on' => 'nullable|date',
            'credential_id' => 'nullable|string|max:120',
            'credential_url' => 'nullable|url|max:255',
        ]);
        $cert = new Certification($data);
        $cert->professional_profile_id = $this->profile($request)->id;
        $cert->save();

        return back()->with('success', 'Certification added — our team may verify it.');
    }

    public function deleteCertification(Request $request, Certification $certification)
    {
        abort_unless($certification->professional_profile_id === $this->profile($request)->id, 403);
        $certification->delete();

        return back()->with('success', 'Removed.');
    }

    public function portfolio(Request $request)
    {
        return view('app.talent.profile.portfolio', ['profile' => $this->profile($request)->load('portfolio')]);
    }

    public function savePortfolio(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'title' => 'required|string|max:120',
            'role' => 'nullable|string|max:120',
            'description' => 'required|string|max:2000',
            'technologies' => 'nullable|string|max:255',
            'outcome' => 'nullable|string|max:1000',
            'url' => 'nullable|url|max:255',
            'repo_url' => 'nullable|url|max:255',
            'image' => 'nullable|image|max:3072',
        ]);
        $profile = $this->profile($request);
        $item = isset($data['id']) ? $profile->portfolio()->findOrFail($data['id']) : new PortfolioItem;
        $item->fill(collect($data)->except(['id', 'image'])->all());
        if ($request->hasFile('image')) {
            $item->image_path = $request->file('image')->store('portfolio', 'public');
        }
        $item->professional_profile_id = $profile->id;
        $item->save();

        return back()->with('success', 'Portfolio updated 💅');
    }

    public function deletePortfolio(Request $request, PortfolioItem $item)
    {
        abort_unless($item->professional_profile_id === $this->profile($request)->id, 403);
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->delete();

        return back()->with('success', 'Removed.');
    }

    public function submit(Request $request)
    {
        $profile = $this->profile($request);
        $list = collect($profile->checklist());
        $required = $list->whereIn('key', ['basics', 'skills', 'primary', 'experience']);
        if ($required->contains('done', false)) {
            return back()->with('error', 'Finish the checklist first: '.$required->where('done', false)->pluck('label')->implode(', ').'.');
        }

        $primaryVerified = $list->firstWhere('key', 'assessment')['done'];
        $status = $primaryVerified ? (setting('auto_conditional_approval') ? 'conditionally_approved' : 'under_review') : 'awaiting_assessment';
        $profile->forceFill(['status' => $status, 'submitted_at' => now()])->save();
        AuditLog::record('profile.submitted', $profile, ['status' => $status]);

        User::whereIn('staff_role', ['admin', 'super_admin'])->get()->each->notify(new PlatformNotification(
            'Profile ready for review', $request->user()->name.' submitted their profile.', route('admin.talent.show', $profile), 'user-check'
        ));

        return back()->with('success', match ($status) {
            'conditionally_approved' => 'You’re live (conditionally) 🚀 Our team will complete the full review soon.',
            'under_review' => 'Submitted! Our team is reviewing your profile.',
            default => 'Submitted! Pass the assessment for your primary skill to go live.',
        });
    }
}
