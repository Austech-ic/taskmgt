<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\ProfileSkill;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    protected function answersFor(AssessmentAttempt $attempt, bool $correct): array
    {
        return Question::whereIn('id', $attempt->question_ids)->get()->mapWithKeys(function ($q) use ($correct) {
            $right = $q->correct[0];
            $wrong = collect($q->options)->pluck('key')->first(fn ($k) => $k !== $right);

            return [$q->id => $correct ? $right : $wrong];
        })->all();
    }

    public function test_answer_keys_are_never_sent_to_the_browser(): void
    {
        $user = $this->talent();
        $assessment = Assessment::where('slug', 'javascript')->first();
        $this->actingAs($user)->post(route('app.assessments.start', $assessment), ['agree' => 1]);
        $attempt = AssessmentAttempt::first();

        $html = $this->actingAs($user)->get(route('app.attempts.take', $attempt))->assertOk()->getContent();
        foreach (Question::whereIn('id', $attempt->question_ids)->whereNotNull('explanation')->get() as $q) {
            $this->assertStringNotContainsString(e($q->explanation), $html);
        }
        $this->assertStringNotContainsString('"correct"', $html);
    }

    public function test_failed_attempt_never_shows_verified(): void
    {
        $user = $this->talent();
        $assessment = Assessment::where('slug', 'javascript')->first();
        $this->actingAs($user)->post(route('app.assessments.start', $assessment), ['agree' => 1])->assertRedirect();
        $attempt = AssessmentAttempt::first();

        $this->actingAs($user)->post(route('app.attempts.submit', $attempt), ['answers' => json_encode($this->answersFor($attempt, false))])->assertRedirect();

        $attempt->refresh();
        $this->assertSame('not_passed', $attempt->status);
        $this->assertNull($attempt->level_awarded);
        $this->assertFalse(ProfileSkill::whereNotNull('verified_level')->exists());

        // Retake cooldown applies.
        $this->actingAs($user)->post(route('app.assessments.start', $assessment), ['agree' => 1])->assertSessionHas('error');
    }

    public function test_passing_attempt_awards_a_verified_badge(): void
    {
        $user = $this->talent();
        $assessment = Assessment::where('slug', 'sql')->first();
        $user->profile->profileSkills()->create(['skill_id' => $assessment->skill_id, 'self_level' => 3, 'years' => 2, 'is_primary' => true]);

        $this->actingAs($user)->post(route('app.assessments.start', $assessment), ['agree' => 1]);
        $attempt = AssessmentAttempt::first();
        $this->travel(5)->minutes();
        $this->actingAs($user)->post(route('app.attempts.submit', $attempt), ['answers' => json_encode($this->answersFor($attempt, true))]);

        $attempt->refresh();
        $this->assertSame('passed', $attempt->status);
        $this->assertSame(100, $attempt->percentage);
        $ps = $user->profile->profileSkills()->where('skill_id', $assessment->skill_id)->first();
        $this->assertSame(5, $ps->verified_level);
        $this->assertNotNull($ps->verification_expires_at);
    }

    public function test_practical_assessments_go_to_human_review_and_reviewer_decides(): void
    {
        $user = $this->talent();
        $assessment = Assessment::where('slug', 'ui-ux-design')->first();
        $this->actingAs($user)->post(route('app.assessments.start', $assessment), ['agree' => 1]);
        $attempt = AssessmentAttempt::first();
        $this->travel(10)->minutes();
        $this->actingAs($user)->post(route('app.attempts.submit', $attempt), [
            'answers' => json_encode($this->answersFor($attempt, true)),
            'practical_url' => 'https://figma.com/file/abc',
            'practical_submission' => 'My rationale',
        ]);
        $this->assertSame('under_review', $attempt->fresh()->status);
        $this->assertFalse(ProfileSkill::whereNotNull('verified_level')->exists(), 'No badge before human review');

        $reviewer = $this->staff('reviewer');
        $this->actingAs($reviewer)->post(route('admin.attempts.decide', $attempt), ['decision' => 'pass', 'level' => 3, 'notes' => 'Solid hierarchy and accessibility choices.'])->assertRedirect();
        $this->assertSame('passed', $attempt->fresh()->status);
        $this->assertSame(3, ProfileSkill::where('skill_id', $assessment->skill_id)->value('verified_level'));
    }

    public function test_users_cannot_view_other_peoples_attempts(): void
    {
        $a = $this->talent();
        $b = $this->talent();
        $this->actingAs($a)->post(route('app.assessments.start', Assessment::first()), ['agree' => 1]);
        $this->actingAs($b)->get(route('app.attempts.take', AssessmentAttempt::first()))->assertForbidden();
    }
}
