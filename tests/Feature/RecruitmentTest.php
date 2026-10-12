<?php

namespace Tests\Feature;

use App\Models\ContactUnlock;
use App\Models\JobPost;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_contact_details_stay_hidden_until_unlocked(): void
    {
        $pro = $this->talent(['contact_email' => 'secret@pro.test', 'contact_phone' => '+2348099999999']);
        $profile = $pro->profile;

        $this->get(route('talent.show', $profile))->assertOk()->assertDontSee('secret@pro.test')->assertDontSee('+2348099999999');

        $hirer = $this->hirer(credits: 0);
        $this->actingAs($hirer)->get(route('talent.show', $profile))->assertOk()->assertDontSee('secret@pro.test');

        // No credits → redirected to billing, nothing unlocked.
        $this->actingAs($hirer)->post(route('app.talent.unlock', $profile))->assertRedirect(route('app.billing'));
        $this->assertSame(0, ContactUnlock::count());
    }

    public function test_unlock_consumes_a_credit_and_reveals_contact(): void
    {
        $pro = $this->talent(['contact_email' => 'secret@pro.test']);
        $hirer = $this->hirer(credits: 2);

        $this->actingAs($hirer)->post(route('app.talent.unlock', $pro->profile))->assertSessionHas('success');
        $this->assertSame(1, $hirer->organization()->fresh()->unlock_credits);
        $this->actingAs($hirer)->get(route('talent.show', $pro->profile))->assertSee('secret@pro.test');

        // Unlocking twice doesn't double-charge.
        $this->actingAs($hirer)->post(route('app.talent.unlock', $pro->profile));
        $this->assertSame(1, $hirer->organization()->fresh()->unlock_credits);
    }

    public function test_professionals_who_opt_out_cannot_be_unlocked(): void
    {
        $pro = $this->talent(['contact_email' => 'secret@pro.test', 'allow_direct_contact' => false]);
        $hirer = $this->hirer(credits: 2);
        $this->actingAs($hirer)->post(route('app.talent.unlock', $pro->profile))->assertSessionHas('error');
        $this->assertSame(2, $hirer->organization()->fresh()->unlock_credits);
    }

    public function test_unapproved_profiles_are_not_searchable(): void
    {
        $draft = $this->talent(['status' => 'draft', 'headline' => 'Hidden draft person']);
        $live = $this->talent(['headline' => 'Visible approved person']);
        $this->get(route('talent.index'))->assertSee('Visible approved person')->assertDontSee('Hidden draft person');
        $this->get(route('talent.show', $draft->profile))->assertNotFound();
    }

    public function test_verified_level_filter_only_returns_verified_people(): void
    {
        $skill = Skill::where('name', 'React')->first();
        $verified = $this->talent(['headline' => 'Verified react person']);
        $verified->profile->profileSkills()->create(['skill_id' => $skill->id, 'verified_level' => 4, 'verified_at' => now(), 'verification_expires_at' => now()->addYear()]);
        $claimed = $this->talent(['headline' => 'Claims react only']);
        $claimed->profile->profileSkills()->create(['skill_id' => $skill->id, 'self_level' => 5]);

        $this->get(route('talent.index', ['skills' => 'React', 'min_level' => 3]))
            ->assertSee('Verified react person')->assertDontSee('Claims react only');
    }

    public function test_job_post_application_and_pipeline(): void
    {
        $hirer = $this->hirer();
        $pro = $this->talent();
        $this->actingAs($hirer)->post(route('app.jobs.store'), [
            'title' => 'Laravel Developer', 'description' => 'Build APIs.', 'employment_type' => 'full_time', 'work_arrangement' => 'remote',
            'seniority' => 'mid', 'currency' => 'NGN', 'openings' => 1, 'status' => 'open',
        ])->assertRedirect();
        $job = JobPost::first();

        $this->actingAs($pro)->post(route('app.jobs.apply', $job), ['cover_letter' => 'Hi!'])->assertRedirect(route('app.applications.index'));
        $application = $job->applications()->first();

        $this->actingAs($hirer)->put(route('app.applications.stage', $application), ['stage' => 'interview', 'note' => 'Strong API design'])->assertRedirect();
        $this->assertSame('interview', $application->fresh()->stage);
        $this->assertSame(2, $application->events()->count());

        // Another company can't touch it.
        $this->actingAs($this->hirer())->put(route('app.applications.stage', $application), ['stage' => 'closed'])->assertForbidden();
    }
}
