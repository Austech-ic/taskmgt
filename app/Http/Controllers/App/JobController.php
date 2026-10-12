<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\JobPost;
use App\Models\SkillCategory;
use App\Notifications\PlatformNotification;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobController extends Controller
{
    protected function org(Request $request)
    {
        return $request->user()->organization();
    }

    public function index(Request $request)
    {
        return view('app.hirer.jobs.index', [
            'jobs' => $this->org($request)->jobs()->withCount(['applications', 'applications as new_count' => fn ($q) => $q->where('stage', 'new')->whereNull('withdrawn_at')])->latest()->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        return view('app.hirer.jobs.form', ['job' => new JobPost(['status' => 'open', 'openings' => 1, 'currency' => 'NGN', 'show_salary' => true]), 'categories' => SkillCategory::with('skills')->orderBy('position')->get(), 'selected' => []]);
    }

    public function store(Request $request)
    {
        $org = $this->org($request);
        $plan = $org->activeSubscription()?->plan;
        $limit = $plan?->active_jobs ?? 1;
        if ($org->jobs()->where('status', 'open')->count() >= $limit && $request->input('status') === 'open') {
            return back()->withInput()->with('error', "Your plan allows {$limit} active ".Str::plural('job', $limit).'. Close one or upgrade to post more.');
        }
        $job = new JobPost;
        $job->organization_id = $org->id;
        $job->posted_by = $request->user()->id;
        $this->persist($request, $job);

        return redirect()->route('app.jobs.pipeline', $job)->with('success', 'Job posted 📣 It’s live on the jobs board.');
    }

    public function edit(Request $request, JobPost $job)
    {
        $this->own($request, $job);

        return view('app.hirer.jobs.form', ['job' => $job, 'categories' => SkillCategory::with('skills')->orderBy('position')->get(), 'selected' => $job->skills->mapWithKeys(fn ($s) => [$s->id => $s->pivot->is_required])->all()]);
    }

    public function update(Request $request, JobPost $job)
    {
        $this->own($request, $job);
        $this->persist($request, $job);

        return redirect()->route('app.jobs.pipeline', $job)->with('success', 'Job updated.');
    }

    protected function persist(Request $request, JobPost $job): void
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'summary' => 'nullable|string|max:300',
            'description' => 'required|string|max:10000',
            'responsibilities' => 'nullable|string|max:4000',
            'benefits' => 'nullable|string|max:3000',
            'employment_type' => 'required|in:'.implode(',', array_keys(Labels::EMPLOYMENT_TYPES)),
            'work_arrangement' => 'required|in:remote,hybrid,onsite',
            'seniority' => 'required|in:'.implode(',', array_keys(Labels::SENIORITY)),
            'location' => 'nullable|string|max:120',
            'currency' => 'required|in:'.implode(',', array_keys(Labels::CURRENCIES)),
            'salary_min' => 'nullable|string|max:20',
            'salary_max' => 'nullable|string|max:20',
            'show_salary' => 'nullable|boolean',
            'openings' => 'required|integer|min:1|max:100',
            'require_verified' => 'nullable|boolean',
            'deadline' => 'nullable|date|after:today',
            'status' => 'required|in:draft,open,closed',
            'skills' => 'nullable|array|max:15',
            'skills.*' => 'integer|exists:skills,id',
            'required_skills' => 'nullable|array',
        ]);
        $job->fill(collect($data)->except(['skills', 'required_skills', 'salary_min', 'salary_max'])->all());
        $job->salary_min = to_minor($data['salary_min'] ?? null);
        $job->salary_max = to_minor($data['salary_max'] ?? null);
        $job->show_salary = $request->boolean('show_salary');
        $job->require_verified = $request->boolean('require_verified');
        if (! $job->slug) {
            $job->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        }
        $job->save();

        $required = array_map('intval', $data['required_skills'] ?? []);
        $job->skills()->sync(collect($data['skills'] ?? [])->mapWithKeys(fn ($id) => [(int) $id => ['is_required' => in_array((int) $id, $required, true)]])->all());
    }

    public function pipeline(Request $request, JobPost $job)
    {
        $this->own($request, $job);
        $applications = $job->applications()->with(['profile.user', 'profile.skills'])->whereNull('withdrawn_at')->latest()->get();

        return view('app.hirer.jobs.pipeline', [
            'job' => $job->load('skills'),
            'columns' => collect(Application::STAGES)->map(fn ($label, $stage) => ['label' => $label, 'items' => $applications->where('stage', $stage)]),
            'withdrawn' => $job->applications()->whereNotNull('withdrawn_at')->count(),
        ]);
    }

    public function moveStage(Request $request, Application $application)
    {
        $this->own($request, $application->job);
        $data = $request->validate(['stage' => 'required|in:'.implode(',', array_keys(Application::STAGES)), 'note' => 'nullable|string|max:1000', 'rating' => 'nullable|integer|between:1,5']);
        $from = $application->stage;
        $application->update(['stage' => $data['stage'], 'rating' => $data['rating'] ?? $application->rating, 'internal_notes' => $data['note'] ? trim($application->internal_notes."\n".now()->format('j M').': '.$data['note']) : $application->internal_notes]);
        if ($from !== $data['stage']) {
            ApplicationEvent::create(['application_id' => $application->id, 'user_id' => $request->user()->id, 'from_stage' => $from, 'to_stage' => $data['stage'], 'note' => $data['note'] ?? null]);
            // Candidates only see candidate-facing stages, never internal notes.
            if (in_array($data['stage'], ['shortlisted', 'interview', 'offer', 'hired', 'closed'], true)) {
                $application->profile->user->notify(new PlatformNotification(
                    'Application update', 'Your application for '.$application->job->title.' moved to: '.Application::STAGES[$data['stage']].'.',
                    route('app.applications.index'), 'send'
                ));
            }
        }

        return back()->with('success', 'Moved to '.Application::STAGES[$data['stage']].'.');
    }

    public function cv(Request $request, Application $application)
    {
        $this->own($request, $application->job);
        abort_unless($application->cv_path && Storage::disk('local')->exists($application->cv_path), 404);

        return Storage::disk('local')->download($application->cv_path, 'CV-'.$application->profile->slug.'.'.pathinfo($application->cv_path, PATHINFO_EXTENSION));
    }

    protected function own(Request $request, JobPost $job): void
    {
        abort_unless($job->organization_id === $this->org($request)?->id, 403);
    }
}
