<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Copilot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** JSON endpoints behind the "✨ Draft with AI" buttons. */
class AiController extends Controller
{
    public function run(Request $request, Copilot $copilot, string $feature)
    {
        try {
            $result = match ($feature) {
                'profile_bio' => $this->bio($request, $copilot),
                'job_description' => $copilot->jobDescription($request->validate([
                    'title' => 'required|string|max:120', 'seniority' => 'nullable|string|max:30', 'employment_type' => 'nullable|string|max:30',
                    'work_arrangement' => 'nullable|string|max:30', 'location' => 'nullable|string|max:120', 'skills' => 'nullable|string|max:400',
                    'company' => 'nullable|string|max:200', 'notes' => 'nullable|string|max:2000',
                ]) + ['company' => $request->user()->organization()?->name]),
                'project_scope' => $copilot->projectScope($request->validate([
                    'title' => 'required|string|max:140', 'idea' => 'required|string|min:20|max:4000', 'budget' => 'nullable|string|max:60', 'deadline' => 'nullable|string|max:40',
                ])),
                'proposal' => $this->proposal($request, $copilot),
                default => abort(404),
            };
        } catch (AiUnavailable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['result' => $result]);
    }

    protected function bio(Request $request, Copilot $copilot): array
    {
        $profile = $request->user()->profile;
        abort_unless($profile, 403);
        $extra = $request->validate(['notes' => 'nullable|string|max:1500'])['notes'] ?? null;
        $profile->load('skills', 'experiences', 'portfolio');

        return $copilot->profileBio([
            'name' => $request->user()->firstName(),
            'role' => $profile->primary_role,
            'years of experience' => $profile->years_experience,
            'location' => $profile->location(),
            'verified skills' => $profile->verifiedSkills()->map(fn ($s) => $s->name.' (Level '.$s->pivot->verified_level.')')->all(),
            'other skills' => $profile->skills->whereNull('pivot.verified_level')->pluck('name')->all(),
            'experience' => $profile->experiences->map(fn ($e) => "{$e->title} at {$e->organization}")->all(),
            'projects' => $profile->portfolio->map(fn ($p) => $p->title.($p->outcome ? " ({$p->outcome})" : ''))->all(),
            'availability' => $profile->availabilityLabel(),
            'extra notes from them' => $extra,
            'current bio' => $profile->bio,
        ]);
    }

    protected function proposal(Request $request, Copilot $copilot): string
    {
        $data = $request->validate(['project_id' => 'required|exists:projects,id', 'notes' => 'nullable|string|max:3000']);
        $project = Project::findOrFail($data['project_id']);
        abort_unless($project->status === 'open', 422);
        $profile = $request->user()->profile;
        abort_unless($profile, 403);
        $profile->load('skills', 'portfolio');

        return $copilot->proposal([
            'project title' => $project->title,
            'project description' => Str::limit($project->description, 1500),
            'deliverables' => $project->deliverables,
            'acceptance criteria' => $project->acceptance_criteria,
            'professional name' => $request->user()->firstName(),
            'professional role' => $profile->primary_role,
            'verified skills' => $profile->verifiedSkills()->map(fn ($s) => $s->name.' L'.$s->pivot->verified_level)->all(),
            'relevant portfolio' => $profile->portfolio->take(3)->map(fn ($p) => $p->title.': '.$p->description)->all(),
            'their draft notes' => $data['notes'] ?? null,
        ]);
    }
}
