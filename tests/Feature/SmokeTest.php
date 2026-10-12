<?php

namespace Tests\Feature;

use App\Models\JobPost;
use App\Models\Post;
use App\Models\ProfessionalProfile;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every page renders for the right audience without errors. */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        (new DemoSeeder)->run();
    }

    public function test_public_pages_render(): void
    {
        $profile = ProfessionalProfile::first();
        $job = JobPost::first();
        $project = Project::first();
        $post = Post::first();

        foreach ([
            route('home'), route('talent.index'), route('talent.index', ['skills' => 'React', 'min_level' => 2]), route('talent.show', $profile),
            route('jobs.index'), route('jobs.show', $job), route('projects.index'), route('projects.show', $project), route('skills.index'),
            route('pricing'), route('how'), route('faq'), route('contact'), route('blog.index'), route('blog.show', $post),
            route('pages.show', 'about'), route('pages.show', 'verification'), route('sitemap'), route('login'), route('register'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_talent_workspace_pages_render(): void
    {
        $user = User::where('email', 'adaeze.okafor@demo.devhail.test')->first();
        foreach (['app.dashboard', 'app.profile.edit', 'app.profile.skills', 'app.profile.experience', 'app.profile.portfolio', 'app.assessments.index', 'app.applications.index', 'app.proposals.index', 'app.contracts.index', 'app.earnings', 'app.requests.index', 'app.messages.index', 'app.notifications', 'app.settings'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
        $this->actingAs($user)->get(route('app.assessments.show', 'react'))->assertOk();
    }

    public function test_hirer_workspace_pages_render(): void
    {
        $user = User::where('email', 'hiring@demo.devhail.test')->first();
        $job = JobPost::first();
        foreach (['app.dashboard', 'app.company.edit', 'app.shortlists.index', 'app.unlocks.index', 'app.billing', 'app.jobs.index', 'app.jobs.create', 'app.projects.index', 'app.projects.create'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
        $this->actingAs($user)->get(route('app.jobs.pipeline', $job))->assertOk();
        $this->actingAs($user)->get(route('app.jobs.edit', $job))->assertOk();
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->staff('super_admin');
        $routes = ['admin.dashboard', 'admin.attempts.index', 'admin.assessments.index', 'admin.assessments.create', 'admin.talent.index', 'admin.users.index', 'admin.organizations.index', 'admin.listings.index', 'admin.reviews.index', 'admin.inbox', 'admin.audit', 'admin.finance.index', 'admin.finance.transactions', 'admin.finance.ledger', 'admin.finance.payouts', 'admin.disputes.index', 'admin.contracts.index'];
        foreach ($routes as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
        foreach (['general', 'homepage', 'marketplace', 'access', 'payments', 'ai'] as $g) {
            $this->actingAs($admin)->get(route('admin.settings', $g))->assertOk();
        }
        foreach (['pages', 'posts', 'faqs', 'testimonials', 'categories', 'skills', 'plans'] as $r) {
            $this->actingAs($admin)->get(route('admin.resources.index', $r))->assertOk();
            $this->actingAs($admin)->get(route('admin.resources.create', $r))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.assessments.edit', 'react'))->assertOk();
        $this->actingAs($admin)->get(route('admin.talent.show', ProfessionalProfile::first()))->assertOk();
    }

    public function test_staff_roles_are_enforced(): void
    {
        $reviewer = $this->staff('reviewer');
        $this->actingAs($reviewer)->get(route('admin.attempts.index'))->assertOk();
        $this->actingAs($reviewer)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($reviewer)->get(route('admin.settings'))->assertForbidden();

        $member = $this->talent();
        $this->actingAs($member)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_ai_features_degrade_gracefully_when_disabled(): void
    {
        $this->postJson(route('assistant'), ['question' => 'How do verified badges work?'])->assertOk()->assertJsonStructure(['answer']);
        $user = User::where('email', 'adaeze.okafor@demo.devhail.test')->first();
        $this->actingAs($user)->postJson(route('app.ai', 'profile_bio'), [])->assertStatus(422)->assertJsonStructure(['message']);
        $this->post(route('talent.ai'), ['prompt' => 'react developer'])->assertRedirect();
    }
}
