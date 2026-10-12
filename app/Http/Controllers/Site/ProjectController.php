<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('status', 'open')->where('visibility', 'public')->with(['skills', 'client', 'organization'])->withCount('proposals')
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%$t%")->orWhere('description', 'like', "%$t%")))
            ->when($request->skill, fn ($q, $s) => $q->whereHas('skills', fn ($k) => $k->where('skills.id', $s)))
            ->when($request->type, fn ($q, $t) => $q->where('contract_type', $t))
            ->latest()->paginate(12)->withQueryString();

        return view('site.projects.index', compact('projects'));
    }

    public function show(Request $request, Project $project)
    {
        $user = $request->user();
        abort_unless($project->status === 'open' || ($user && ($user->id === $project->client_id || $user->isStaff())), 404);
        $project->increment('views');
        $project->load(['skills', 'client', 'organization'])->loadCount('proposals');
        $proposal = $user?->profile ? $project->proposals()->where('professional_profile_id', $user->profile->id)->first() : null;

        return view('site.projects.show', compact('project', 'proposal'));
    }
}
