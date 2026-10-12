<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use App\Models\Skill;
use App\Support\Labels;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = JobPost::open()->with(['organization', 'skills'])
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%$t%")->orWhere('summary', 'like', "%$t%")->orWhere('description', 'like', "%$t%")))
            ->when($request->type, fn ($q, $t) => $q->where('employment_type', $t))
            ->when($request->arrangement, fn ($q, $a) => $q->where('work_arrangement', $a))
            ->when($request->seniority, fn ($q, $s) => $q->where('seniority', $s))
            ->when($request->skill, fn ($q, $s) => $q->whereHas('skills', fn ($k) => $k->where('skills.id', $s)))
            ->latest()->paginate(12)->withQueryString();

        return view('site.jobs.index', [
            'jobs' => $jobs,
            'skills' => Skill::whereHas('assessments')->orWhereIn('id', fn ($q) => $q->select('skill_id')->from('job_post_skill'))->orderBy('name')->get(),
            'types' => Labels::EMPLOYMENT_TYPES,
        ]);
    }

    public function show(Request $request, JobPost $job)
    {
        $user = $request->user();
        $canSee = $job->status === 'open' || ($user && ($user->organizations->contains($job->organization_id) || $user->isStaff()));
        abort_unless($canSee, 404);
        $job->increment('views');
        $job->load(['organization', 'skills']);

        $application = $user?->profile ? $job->applications()->where('professional_profile_id', $user->profile->id)->first() : null;
        $missing = collect();
        if ($user?->profile) {
            $verified = $user->profile->profileSkills()->whereNotNull('verified_level')->pluck('skill_id');
            $missing = $job->skills->where('pivot.is_required', true)->whereNotIn('id', $verified);
        }

        return view('site.jobs.show', compact('job', 'application', 'missing'));
    }
}
