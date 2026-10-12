<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AuditLog;
use App\Models\ProfileSkill;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssessmentService
{
    public function start(User $user, Assessment $assessment): AssessmentAttempt
    {
        if (! $assessment->isPublished()) {
            throw new RuntimeException('This assessment is not open right now.');
        }
        if ($open = $assessment->attempts()->where('user_id', $user->id)->where('status', 'in_progress')->first()) {
            if ($open->isOpen()) {
                return $open;
            }
            $this->submit($open, $open->answers ?? [], timedOut: true);
        }
        if ($at = $assessment->nextEligibleAt($user)) {
            throw new RuntimeException('You can take this assessment again from '.$at->format('j M Y').'.');
        }

        $pool = $assessment->activeQuestions()->get(['id', 'difficulty']);
        if ($pool->isEmpty()) {
            throw new RuntimeException('This assessment has no questions yet.');
        }

        // Randomised selection, preferring less-exposed questions to slow leakage.
        $picked = $pool->shuffle()->sortBy(fn ($q) => random_int(0, 3))->take($assessment->question_count)->pluck('id')->shuffle()->values()->all();
        DB::table('questions')->whereIn('id', $picked)->increment('exposure_count');

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'assessment_version' => $assessment->version,
            'question_ids' => $picked,
            'answers' => [],
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes($assessment->duration_minutes),
        ]);
        AuditLog::record('assessment.started', $attempt);

        return $attempt;
    }

    /** Score server-side. Answer keys never reach the browser. */
    public function submit(AssessmentAttempt $attempt, array $answers, ?string $practical = null, ?string $practicalUrl = null, bool $timedOut = false, int $focusLosses = 0): AssessmentAttempt
    {
        if ($attempt->status !== 'in_progress') {
            return $attempt;
        }

        $assessment = $attempt->assessment;
        $questions = $attempt->questions();
        $score = 0;
        $max = 0;
        $topics = [];

        foreach ($questions as $q) {
            $earned = $q->score($answers[$q->id] ?? null);
            $score += $earned;
            $max += $q->points;
            $topic = $q->topic ?: 'General';
            $topics[$topic]['earned'] = ($topics[$topic]['earned'] ?? 0) + $earned;
            $topics[$topic]['max'] = ($topics[$topic]['max'] ?? 0) + $q->points;
        }

        $pct = $max > 0 ? (int) floor($score / $max * 100) : 0;
        $flags = [];
        $grace = 30;
        if (now()->gt($attempt->expires_at->copy()->addSeconds($grace)) && ! $timedOut) {
            $flags[] = 'submitted_after_time_limit';
        }
        if ($focusLosses >= 5) {
            $flags[] = "left_the_test_tab_{$focusLosses}_times";
        }
        $elapsed = $attempt->started_at->diffInSeconds(now());
        if ($questions->count() >= 5 && $elapsed < $questions->count() * 8 && $pct >= 90) {
            $flags[] = 'unusually_fast_high_score';
        }

        $meetsKnowledgeBar = $pct >= $assessment->pass_mark;
        // Practical work or integrity flags always route to a human. Flags never auto-fail anyone.
        $needsHuman = $meetsKnowledgeBar && ($assessment->requires_review || ! empty($flags));

        $status = match (true) {
            $needsHuman => 'under_review',
            $meetsKnowledgeBar => 'passed',
            default => 'not_passed',
        };

        $attempt->update([
            'answers' => $answers,
            'component_scores' => $topics,
            'practical_submission' => $practical,
            'practical_url' => $practicalUrl,
            'score' => $score,
            'max_score' => $max,
            'percentage' => $pct,
            'integrity_flags' => $flags ?: null,
            'focus_losses' => $focusLosses,
            'submitted_at' => now(),
            'status' => $status,
            'feedback' => $this->feedback($topics, $meetsKnowledgeBar),
            'level_awarded' => $status === 'passed' ? $this->levelFor($pct) : null,
        ]);

        if ($status === 'passed') {
            $this->awardVerification($attempt);
        }
        AuditLog::record('assessment.submitted', $attempt, ['status' => $status, 'pct' => $pct, 'flags' => $flags]);

        return $attempt->refresh();
    }

    public function review(AssessmentAttempt $attempt, User $reviewer, bool $pass, ?int $level, ?string $notes): void
    {
        if ($attempt->status !== 'under_review') {
            throw new RuntimeException('Only attempts under review can be decided.');
        }
        $attempt->update([
            'status' => $pass ? 'passed' : 'not_passed',
            'level_awarded' => $pass ? ($level ?: $this->levelFor($attempt->percentage)) : null,
            'reviewer_id' => $reviewer->id,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
        if ($pass) {
            $this->awardVerification($attempt);
        } else {
            $attempt->user->notify(new PlatformNotification(
                'Assessment result: not yet 🌱',
                "Your {$attempt->assessment->skill->name} attempt didn't clear the bar this time. Check the feedback and come back stronger.",
                route('app.attempts.result', $attempt),
                'sprout'
            ));
        }
        AuditLog::record('assessment.reviewed', $attempt, ['pass' => $pass, 'level' => $level]);
    }

    public function levelFor(int $pct): int
    {
        return match (true) {
            $pct >= 95 => 5,
            $pct >= 88 => 4,
            $pct >= 78 => 3,
            $pct >= 65 => 2,
            default => 1,
        };
    }

    protected function awardVerification(AssessmentAttempt $attempt): void
    {
        $user = $attempt->user;
        $profile = $user->profile;
        if (! $profile) {
            return;
        }
        $assessment = $attempt->assessment;

        $ps = ProfileSkill::firstOrNew(['professional_profile_id' => $profile->id, 'skill_id' => $assessment->skill_id]);
        $ps->fill([
            'verified_level' => $attempt->level_awarded,
            'verified_score' => $attempt->percentage,
            'verified_at' => now(),
            'verification_expires_at' => now()->addMonths($assessment->validity_months),
            'assessment_attempt_id' => $attempt->id,
        ]);
        if (! $ps->exists) {
            $ps->self_level = $attempt->level_awarded;
        }
        $ps->save();

        $profile->forceFill(['last_verified_at' => now()])->save();

        // A passed primary skill lets a submitted profile go live (conditionally) without waiting.
        if ($ps->is_primary && in_array($profile->status, ['awaiting_assessment', 'under_review'], true) && setting('auto_conditional_approval')) {
            $profile->forceFill(['status' => 'conditionally_approved'])->save();
        }

        $user->notify(new PlatformNotification(
            'Verified! '.$assessment->skill->name.' ✅',
            'You passed at Level '.$attempt->level_awarded.' ('.level_name($attempt->level_awarded).'). Your badge is live on your profile.',
            route('app.attempts.result', $attempt),
            'badge-check'
        ));
    }

    protected function feedback(array $topics, bool $passed): string
    {
        $weak = collect($topics)->filter(fn ($t) => $t['max'] > 0 && $t['earned'] / $t['max'] < 0.6)->keys();
        $strong = collect($topics)->filter(fn ($t) => $t['max'] > 0 && $t['earned'] / $t['max'] >= 0.8)->keys();

        $lines = [];
        if ($strong->isNotEmpty()) {
            $lines[] = 'Strong areas: '.$strong->implode(', ').'.';
        }
        if ($weak->isNotEmpty()) {
            $lines[] = 'Worth levelling up: '.$weak->implode(', ').'.';
        }
        $lines[] = $passed ? 'Nice work — this result is now evidence on your profile.' : 'Not yet passed. Use the time before your retake to practise the areas above.';

        return implode(' ', $lines);
    }
}
