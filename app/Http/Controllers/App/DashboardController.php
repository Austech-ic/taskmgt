<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\ContactRequest;
use App\Models\Contract;
use App\Models\JobPost;
use App\Models\ProfessionalProfile;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ShortlistItem;
use App\Services\Ledger;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->is_talent && ! $user->is_hirer) {
            return $user->isStaff() ? redirect()->route('admin.dashboard') : redirect()->route('app.onboarding');
        }

        return workspace() === 'hirer' ? $this->hirer($request) : $this->talent($request);
    }

    protected function talent(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        if (! $profile) {
            return redirect()->route('app.onboarding', ['want' => 'talent']);
        }
        $profile->load('skills');

        $claimedIds = $profile->skills->pluck('id');

        return view('app.talent.dashboard', [
            'profile' => $profile,
            'checklist' => $profile->checklist(),
            'completion' => $profile->completion(),
            'suggested' => Assessment::where('status', 'published')->whereIn('skill_id', $claimedIds)->with('skill')
                ->whereNotIn('skill_id', $profile->profileSkills()->whereNotNull('verified_level')->pluck('skill_id'))->take(3)->get(),
            'attempts' => $user->attempts()->with('assessment.skill')->latest()->take(4)->get(),
            'jobs' => JobPost::open()->with('organization')
                ->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $claimedIds))->latest()->take(4)->get(),
            'projects' => Project::where('status', 'open')->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $claimedIds))->latest()->take(4)->get(),
            'contracts' => Contract::where('professional_id', $user->id)->whereIn('status', ['active', 'pending_acceptance', 'disputed'])->with('milestones')->latest()->take(3)->get(),
            'stats' => [
                'views' => $profile->profile_views,
                'applications' => Application::where('professional_profile_id', $profile->id)->whereNull('withdrawn_at')->count(),
                'proposals' => Proposal::where('professional_profile_id', $profile->id)->where('status', 'submitted')->count(),
                'earnings' => app(Ledger::class)->balanceOf($user),
                'requests' => ContactRequest::where('professional_profile_id', $profile->id)->where('status', 'pending')->count(),
            ],
        ]);
    }

    protected function hirer(Request $request)
    {
        $user = $request->user();
        $org = $user->organization();

        return view('app.hirer.dashboard', [
            'org' => $org,
            'subscription' => $org?->activeSubscription(),
            'jobs' => $org ? $org->jobs()->withCount(['applications as new_count' => fn ($q) => $q->where('stage', 'new')->whereNull('withdrawn_at'), 'applications'])->latest()->take(5)->get() : collect(),
            'projects' => $user->clientProjects()->withCount('proposals')->latest()->take(5)->get(),
            'contracts' => Contract::where('client_id', $user->id)->whereIn('status', ['active', 'pending_acceptance', 'disputed'])->with('milestones', 'professional')->latest()->take(4)->get(),
            'recent' => ProfessionalProfile::discoverable()->with(['user', 'skills'])->latest('last_verified_at')->take(3)->get(),
            'stats' => [
                'unlocks' => $org?->unlocksAvailable() ?? 0,
                'shortlisted' => $org ? ShortlistItem::whereIn('shortlist_id', $org->shortlists()->pluck('id'))->count() : 0,
                'applicants' => $org ? Application::whereIn('job_post_id', $org->jobs()->pluck('id'))->whereNull('withdrawn_at')->count() : 0,
                'proposals' => Proposal::whereIn('project_id', $user->clientProjects()->pluck('id'))->where('status', 'submitted')->count(),
            ],
        ]);
    }

    public function onboarding(Request $request)
    {
        $user = $request->user();
        $want = $request->query('want', $user->is_talent ? 'hirer' : 'talent');

        return view('app.onboarding', [
            'want' => in_array($want, ['talent', 'hirer'], true) ? $want : 'talent',
            'user' => $user,
            'roles' => ['Frontend Engineer', 'Backend Engineer', 'Full-stack Developer', 'Mobile Engineer', 'Data Engineer', 'Data Scientist', 'ML / AI Engineer', 'DevOps Engineer', 'Cloud Engineer', 'Security Engineer', 'QA Engineer', 'Product Designer', 'Product Manager', 'Business Analyst', 'Network Engineer', 'IT Project Manager'],
            'availability' => Labels::AVAILABILITY,
        ]);
    }

    public function saveOnboarding(Request $request)
    {
        $user = $request->user();
        $want = $request->validate(['want' => 'required|in:talent,hirer'])['want'];

        if ($want === 'talent') {
            $data = $request->validate([
                'primary_role' => 'required|string|max:80',
                'city' => 'nullable|string|max:80',
                'country' => 'required|string|max:80',
                'availability' => 'required|in:'.implode(',', array_keys(Labels::AVAILABILITY)),
                'years_experience' => 'required|integer|min:0|max:50',
                'phone' => 'nullable|string|max:40',
            ]);
            if (! $user->profile) {
                $slug = Str::slug($user->name);
                $base = $slug ?: 'pro';
                $i = 1;
                while (ProfessionalProfile::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $profile = new ProfessionalProfile([
                    'primary_role' => $data['primary_role'], 'city' => $data['city'], 'country' => $data['country'],
                    'availability' => $data['availability'], 'years_experience' => $data['years_experience'],
                    'contact_email' => $user->email, 'contact_phone' => $data['phone'] ?? null,
                ]);
                $profile->user_id = $user->id;
                $profile->slug = $slug;
                $profile->save();
            }
            $user->forceFill(['is_talent' => true, 'phone' => $data['phone'] ?? $user->phone])->save();
            session(['workspace' => 'talent']);

            return redirect()->route('app.profile.skills')->with('success', 'Profile created 🎉 Now claim your skills — that’s where the magic starts.');
        }

        $user->forceFill(['is_hirer' => true])->save();
        session(['workspace' => 'hirer']);

        return redirect()->route($request->input('intent') === 'project' ? 'app.projects.create' : 'app.company.edit')
            ->with('success', 'Hiring workspace unlocked 🔓');
    }

    public function switch(Request $request, string $workspace)
    {
        abort_unless(in_array($workspace, ['talent', 'hirer'], true), 404);
        session(['workspace' => $workspace]);

        return redirect()->route('app.dashboard');
    }
}
