<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\ContactRequest;
use App\Models\Conversation;
use App\Models\JobPost;
use App\Models\Project;
use App\Models\Proposal;
use App\Notifications\PlatformNotification;
use Illuminate\Http\Request;

class TalentWorkController extends Controller
{
    public function applications(Request $request)
    {
        return view('app.talent.applications', [
            'applications' => Application::where('professional_profile_id', $request->user()->profile->id)
                ->with(['job.organization', 'events'])->latest()->paginate(15),
        ]);
    }

    public function apply(Request $request, JobPost $job)
    {
        $profile = $request->user()->profile;
        abort_unless($job->status === 'open', 422, 'This job is no longer accepting applications.');
        $data = $request->validate([
            'cover_letter' => 'nullable|string|max:3000',
            'expected_salary' => 'nullable|string|max:20',
        ]);

        if ($job->require_verified) {
            $verified = $profile->profileSkills()->whereNotNull('verified_level')->pluck('skill_id');
            $missing = $job->skills()->wherePivot('is_required', true)->whereNotIn('skills.id', $verified)->count();
            if ($missing) {
                return back()->with('error', 'This role requires verified skills you haven’t passed yet.');
            }
        }

        $application = Application::firstOrCreate(
            ['job_post_id' => $job->id, 'professional_profile_id' => $profile->id],
            ['cover_letter' => $data['cover_letter'] ?? null, 'expected_salary' => to_minor($data['expected_salary'] ?? null), 'cv_path' => $profile->cv_path, 'stage' => 'new']
        );
        if ($application->wasRecentlyCreated) {
            ApplicationEvent::create(['application_id' => $application->id, 'user_id' => $request->user()->id, 'to_stage' => 'new', 'note' => 'Applied']);
            $job->poster->notify(new PlatformNotification('New applicant 👀', $request->user()->name.' applied to '.$job->title.'.', route('app.jobs.pipeline', $job), 'user-plus'));
        }

        return redirect()->route('app.applications.index')->with('success', 'Applied! Good luck 🍀 Track progress here.');
    }

    public function withdraw(Request $request, Application $application)
    {
        abort_unless($application->professional_profile_id === $request->user()->profile->id, 403);
        $application->update(['withdrawn_at' => now()]);
        ApplicationEvent::create(['application_id' => $application->id, 'user_id' => $request->user()->id, 'from_stage' => $application->stage, 'to_stage' => 'closed', 'note' => 'Withdrawn by candidate']);

        return back()->with('success', 'Application withdrawn.');
    }

    public function proposals(Request $request)
    {
        return view('app.talent.proposals', [
            'proposals' => Proposal::where('professional_profile_id', $request->user()->profile->id)->with('project')->latest()->paginate(15),
        ]);
    }

    public function propose(Request $request, Project $project)
    {
        $profile = $request->user()->profile;
        abort_unless($project->status === 'open' && $project->client_id !== $request->user()->id, 422);
        if (! $profile->isDiscoverable()) {
            return back()->with('error', 'Your profile needs to be approved before you can send proposals. Finish your checklist on the dashboard.');
        }
        if ($project->min_verified_level) {
            $ok = $profile->profileSkills()->whereIn('skill_id', $project->skills()->pluck('skills.id'))->where('verified_level', '>=', $project->min_verified_level)->exists();
            if (! $ok) {
                return back()->with('error', 'This client asked for a verified level of '.$project->min_verified_level.'+ in one of the listed skills.');
            }
        }

        $data = $request->validate([
            'cover_letter' => 'required|string|min:40|max:5000',
            'approach' => 'nullable|string|max:3000',
            'duration_days' => 'required|integer|min:1|max:365',
            'milestones' => 'required|array|min:1|max:10',
            'milestones.*.title' => 'required|string|max:120',
            'milestones.*.amount' => 'required|string|max:20',
            'milestones.*.days' => 'nullable|integer|min:1|max:365',
        ]);
        $milestones = collect($data['milestones'])->map(fn ($m) => ['title' => $m['title'], 'amount_minor' => to_minor($m['amount']), 'days' => $m['days'] ?? null])
            ->filter(fn ($m) => $m['amount_minor'] > 0)->values();
        if ($milestones->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one milestone with an amount.');
        }

        Proposal::updateOrCreate(
            ['project_id' => $project->id, 'professional_profile_id' => $profile->id],
            ['cover_letter' => $data['cover_letter'], 'approach' => $data['approach'] ?? null, 'duration_days' => $data['duration_days'],
                'milestones' => $milestones->all(), 'amount_minor' => $milestones->sum('amount_minor'), 'status' => 'submitted']
        );
        $project->client->notify(new PlatformNotification('New proposal 📨', $request->user()->name.' pitched for “'.$project->title.'”.', route('app.projects.proposals', $project), 'file-text'));

        return redirect()->route('app.proposals.index')->with('success', 'Proposal sent 🚀');
    }

    public function withdrawProposal(Request $request, Proposal $proposal)
    {
        abort_unless($proposal->professional_profile_id === $request->user()->profile->id && in_array($proposal->status, ['submitted', 'shortlisted'], true), 403);
        $proposal->update(['status' => 'withdrawn']);

        return back()->with('success', 'Proposal withdrawn.');
    }

    public function requests(Request $request)
    {
        return view('app.talent.requests', [
            'requests' => ContactRequest::where('professional_profile_id', $request->user()->profile->id)->with(['organization', 'sender'])->latest()->paginate(15),
        ]);
    }

    public function respond(Request $request, ContactRequest $contactRequest, string $decision)
    {
        abort_unless($contactRequest->professional_profile_id === $request->user()->profile->id && $contactRequest->status === 'pending', 403);
        $contactRequest->update(['status' => $decision === 'accept' ? 'accepted' : 'declined']);

        if ($decision === 'accept') {
            $conversation = Conversation::between($request->user(), $contactRequest->sender, $contactRequest->subject, $contactRequest);
            if (! $conversation->messages()->exists()) {
                $conversation->messages()->create(['user_id' => $contactRequest->sender_id, 'body' => $contactRequest->message]);
            }
            $contactRequest->sender->notify(new PlatformNotification('Request accepted 🙌', $request->user()->name.' accepted your contact request. Say hi!', route('app.messages.show', $conversation), 'message-square'));

            return redirect()->route('app.messages.show', $conversation);
        }

        return back()->with('success', 'Request declined. They won’t see your details.');
    }
}
