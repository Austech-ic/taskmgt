<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\SkillCategory;
use App\Services\ContractService;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        return view('app.hirer.projects.index', [
            'projects' => $request->user()->clientProjects()->withCount(['proposals as open_proposals' => fn ($q) => $q->whereIn('status', ['submitted', 'shortlisted'])])->with('contract')->latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return view('app.hirer.projects.form', ['project' => new Project(['currency' => 'NGN', 'contract_type' => 'fixed', 'experience_level' => 'mid', 'status' => 'open']), 'categories' => SkillCategory::with('skills')->orderBy('position')->get(), 'selected' => []]);
    }

    public function store(Request $request)
    {
        $project = new Project;
        $project->client_id = $request->user()->id;
        $project->organization_id = $request->user()->organization()?->id;
        $this->persist($request, $project);

        return redirect()->route('projects.show', $project)->with('success', 'Project is live 🚀 Proposals will roll in here and in your notifications.');
    }

    public function edit(Request $request, Project $project)
    {
        $this->own($request, $project);

        return view('app.hirer.projects.form', ['project' => $project, 'categories' => SkillCategory::with('skills')->orderBy('position')->get(), 'selected' => $project->skills->pluck('id')->all()]);
    }

    public function update(Request $request, Project $project)
    {
        $this->own($request, $project);
        abort_if(in_array($project->status, ['in_progress', 'completed'], true), 422, 'Projects with a contract can’t be edited.');
        $this->persist($request, $project);

        return redirect()->route('app.projects.index')->with('success', 'Project updated.');
    }

    protected function persist(Request $request, Project $project): void
    {
        $data = $request->validate([
            'title' => 'required|string|max:140',
            'description' => 'required|string|min:40|max:10000',
            'deliverables' => 'nullable|string|max:4000',
            'acceptance_criteria' => 'nullable|string|max:4000',
            'currency' => 'required|in:'.implode(',', array_keys(Labels::CURRENCIES)),
            'budget_min' => 'nullable|string|max:20',
            'budget_max' => 'nullable|string|max:20',
            'contract_type' => 'required|in:fixed,hourly',
            'experience_level' => 'required|in:'.implode(',', array_keys(Labels::SENIORITY)),
            'min_verified_level' => 'nullable|integer|between:1,5',
            'deadline' => 'nullable|date|after:today',
            'ip_terms' => 'nullable|string|max:2000',
            'status' => 'required|in:draft,open,cancelled',
            'skills' => 'nullable|array|max:10',
            'skills.*' => 'integer|exists:skills,id',
        ]);
        $project->fill(collect($data)->except(['skills', 'budget_min', 'budget_max'])->all());
        $project->budget_min = to_minor($data['budget_min'] ?? null);
        $project->budget_max = to_minor($data['budget_max'] ?? null);
        if (! $project->slug) {
            $project->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        }
        $project->save();
        $project->skills()->sync($data['skills'] ?? []);
    }

    public function proposals(Request $request, Project $project)
    {
        $this->own($request, $project);

        return view('app.hirer.projects.proposals', [
            'project' => $project,
            'proposals' => $project->proposals()->with('profile.user', 'profile.skills')->whereNotIn('status', ['withdrawn'])
                ->orderByRaw("case status when 'accepted' then 0 when 'shortlisted' then 1 when 'submitted' then 2 else 3 end")->latest()->get(),
        ]);
    }

    public function shortlistProposal(Request $request, Project $project, Proposal $proposal)
    {
        $this->own($request, $project);
        abort_unless($proposal->project_id === $project->id, 404);
        $proposal->update(['status' => $request->input('action') === 'decline' ? 'declined' : 'shortlisted']);

        return back()->with('success', $proposal->status === 'declined' ? 'Proposal declined.' : 'Shortlisted ⭐');
    }

    public function hireForm(Request $request, Project $project, Proposal $proposal)
    {
        $this->own($request, $project);
        abort_unless($proposal->project_id === $project->id && in_array($proposal->status, ['submitted', 'shortlisted'], true) && $project->status === 'open', 404);

        return view('app.hirer.projects.hire', ['project' => $project, 'proposal' => $proposal->load('profile.user')]);
    }

    public function hire(Request $request, Project $project, Proposal $proposal, ContractService $contracts)
    {
        $this->own($request, $project);
        abort_unless($proposal->project_id === $project->id && in_array($proposal->status, ['submitted', 'shortlisted'], true) && $project->status === 'open', 404);
        $data = $request->validate([
            'scope' => 'required|string|min:20|max:10000',
            'acceptance_criteria' => 'required|string|min:10|max:5000',
            'milestones' => 'required|array|min:1|max:10',
            'milestones.*.title' => 'required|string|max:120',
            'milestones.*.amount' => 'required|string|max:20',
            'milestones.*.due_on' => 'nullable|date|after:today',
            'milestones.*.description' => 'nullable|string|max:1000',
            'agree' => 'accepted',
        ], ['agree.accepted' => 'Please confirm the contract terms.']);

        try {
            $contract = $contracts->createFromProposal($proposal, $data);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('app.contracts.show', $contract)->with('success', 'Contract sent to '.$proposal->profile->user->firstName().' 🤝 Fund the first milestone once they accept.');
    }

    protected function own(Request $request, Project $project): void
    {
        abort_unless($project->client_id === $request->user()->id, 403);
    }
}
