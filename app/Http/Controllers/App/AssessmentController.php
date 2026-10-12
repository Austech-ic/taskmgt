<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Services\AssessmentService;
use Illuminate\Http\Request;
use RuntimeException;

class AssessmentController extends Controller
{
    public function __construct(protected AssessmentService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile->load('profileSkills');
        $claimed = $profile->profileSkills->keyBy('skill_id');

        $assessments = Assessment::where('status', 'published')->with('skill.category')->get()
            ->map(function ($a) use ($user, $claimed) {
                $a->claimed = $claimed->get($a->skill_id);
                $a->next_at = $a->nextEligibleAt($user);
                $a->last_attempt = $a->attempts()->where('user_id', $user->id)->latest('id')->first();

                return $a;
            })
            ->sortByDesc(fn ($a) => ($a->claimed ? 2 : 0) + ($a->claimed?->is_primary ? 1 : 0));

        return view('app.talent.assessments.index', [
            'assessments' => $assessments,
            'attempts' => $user->attempts()->with('assessment.skill')->latest()->paginate(10),
        ]);
    }

    public function show(Request $request, Assessment $assessment)
    {
        abort_unless($assessment->isPublished(), 404);
        $user = $request->user();

        return view('app.talent.assessments.show', [
            'assessment' => $assessment->load('skill'),
            'nextAt' => $assessment->nextEligibleAt($user),
            'open' => $assessment->attempts()->where('user_id', $user->id)->where('status', 'in_progress')->first(),
            'history' => $assessment->attempts()->where('user_id', $user->id)->latest()->get(),
            'freeLeft' => max(0, (int) setting('free_assessments') - $user->attempts()->count()),
        ]);
    }

    public function start(Request $request, Assessment $assessment)
    {
        $request->validate(['agree' => 'accepted'], ['agree.accepted' => 'Please confirm you’ll follow the assessment rules.']);
        try {
            $attempt = $this->service->start($request->user(), $assessment);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('app.attempts.take', $attempt);
    }

    public function take(Request $request, AssessmentAttempt $attempt)
    {
        $this->authorizeAttempt($request, $attempt);
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('app.attempts.result', $attempt);
        }
        if (! $attempt->isOpen()) {
            $this->service->submit($attempt, $attempt->answers ?? [], timedOut: true);

            return redirect()->route('app.attempts.result', $attempt)->with('info', 'Time ran out — we submitted what you had.');
        }

        return view('app.talent.assessments.take', [
            'attempt' => $attempt,
            'assessment' => $attempt->assessment,
            // Questions are sent WITHOUT answer keys (hidden on the model).
            'questions' => $attempt->questions(),
        ]);
    }

    public function save(Request $request, AssessmentAttempt $attempt)
    {
        $this->authorizeAttempt($request, $attempt);
        abort_unless($attempt->isOpen(), 409, 'This attempt is closed.');
        $data = $request->validate(['answers' => 'nullable|array', 'focus_losses' => 'nullable|integer|min:0']);
        $attempt->update([
            'answers' => $this->clean($attempt, $data['answers'] ?? []),
            'focus_losses' => max($attempt->focus_losses, (int) ($data['focus_losses'] ?? 0)),
        ]);

        return response()->json(['saved' => true]);
    }

    public function submit(Request $request, AssessmentAttempt $attempt)
    {
        $this->authorizeAttempt($request, $attempt);
        $data = $request->validate([
            'answers' => 'nullable|string|max:65000',
            'focus_losses' => 'nullable|integer|min:0',
            'practical_submission' => 'nullable|string|max:10000',
            'practical_url' => 'nullable|url|max:500',
        ]);
        $answers = $this->clean($attempt, json_decode($data['answers'] ?? '[]', true) ?: []);
        $timedOut = $attempt->expires_at->copy()->addSeconds(30)->isPast();

        $attempt = $this->service->submit($attempt, $answers, $data['practical_submission'] ?? null, $data['practical_url'] ?? null, $timedOut, max($attempt->focus_losses, (int) ($data['focus_losses'] ?? 0)));

        return redirect()->route('app.attempts.result', $attempt);
    }

    public function result(Request $request, AssessmentAttempt $attempt)
    {
        $this->authorizeAttempt($request, $attempt);
        abort_if($attempt->status === 'in_progress', 404);

        return view('app.talent.assessments.result', ['attempt' => $attempt->load('assessment.skill')]);
    }

    protected function authorizeAttempt(Request $request, AssessmentAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
    }

    /** Keep only answers for questions in this attempt. */
    protected function clean(AssessmentAttempt $attempt, array $answers): array
    {
        $ids = array_map('intval', $attempt->question_ids ?? []);

        return collect($answers)->filter(fn ($v, $k) => in_array((int) $k, $ids, true))
            ->map(fn ($v) => is_array($v) ? array_values(array_map('strval', $v)) : (is_scalar($v) ? mb_substr((string) $v, 0, 500) : null))
            ->all();
    }
}
