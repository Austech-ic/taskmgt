<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\JobPost;
use App\Models\Organization;
use App\Models\ProfessionalProfile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Optional demo data so a fresh install doesn't look empty.
 * Every demo account uses the password "password" and an @demo.devhail.test address.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            ['Adaeze Okafor', 'Frontend engineer who turns Figma into fast, accessible React', 'Lagos', 6, 'open_to_work', 'remote', ['React' => 4, 'TypeScript' => null, 'JavaScript' => 4, 'Web accessibility' => null], 'Frontend Engineer'],
            ['Tunde Bakare', 'Backend engineer · Laravel APIs that scale past Black Friday', 'Ibadan', 7, 'contract', 'remote', ['Laravel' => 4, 'PHP' => null, 'SQL' => 3, 'REST API design' => null], 'Backend Engineer'],
            ['Zainab Musa', 'Data engineer building pipelines finance teams can trust', 'Abuja', 5, 'open_to_work', 'hybrid', ['Python' => 4, 'SQL' => 4, 'Data engineering' => null], 'Data Engineer'],
            ['Chidi Eze', 'DevOps engineer · containers, CI/CD and calm on-call rotations', 'Port Harcourt', 8, 'freelance', 'remote', ['Docker' => 5, 'Kubernetes' => null, 'AWS' => null, 'CI/CD' => null], 'DevOps Engineer'],
            ['Funmi Adeyemi', 'Product designer obsessed with onboarding that converts', 'Lagos', 4, 'open_to_work', 'any', ['UI/UX design' => 3, 'Product management' => null], 'Product Designer'],
            ['Ibrahim Sani', 'AppSec engineer helping fintechs ship secure by default', 'Kano', 6, 'contract', 'remote', ['Application security' => 4, 'Python' => 3, 'Linux administration' => null], 'Security Engineer'],
            ['Kemi Ogunleye', 'Full-stack JS dev · Next.js front, Node back, good vibes throughout', 'Lagos', 3, 'freelance', 'remote', ['JavaScript' => 3, 'React' => 3, 'Node.js' => null], 'Full-stack Developer'],
            ['Emeka Nwosu', 'Mobile engineer shipping Flutter apps for low-bandwidth markets', 'Enugu', 5, 'open_to_work', 'remote', ['Flutter' => null, 'JavaScript' => 2], 'Mobile Engineer'],
        ];

        foreach ($people as $i => [$name, $headline, $city, $years, $availability, $arrangement, $skills, $role]) {
            $email = Str::slug($name, '.').'@demo.devhail.test';
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill(['name' => $name, 'password' => Hash::make('password'), 'is_talent' => true, 'email_verified_at' => now(), 'terms_accepted_at' => now(), 'status' => 'active'])->save();

            $profile = ProfessionalProfile::firstOrNew(['user_id' => $user->id]);
            $profile->forceFill([
                'user_id' => $user->id,
                'slug' => Str::slug($name),
                'headline' => $headline,
                'primary_role' => $role,
                'bio' => 'Hi, I’m '.explode(' ', $name)[0].". I’ve spent {$years} years building products for startups and growing companies across Africa. I care about clean, well-tested work and clear communication.\n\nCurrently {$this->availabilityLine($availability)}.",
                'city' => $city,
                'country' => 'Nigeria',
                'years_experience' => $years,
                'availability' => $availability,
                'work_arrangement' => $arrangement,
                'rate_min' => (300000 + $years * 80000) * 100,
                'rate_max' => (450000 + $years * 120000) * 100,
                'rate_unit' => 'month',
                'show_rate' => $i % 2 === 0,
                'languages' => 'English, '.['Igbo', 'Yoruba', 'Hausa'][$i % 3],
                'github_url' => 'https://github.com/',
                'contact_email' => $email,
                'contact_phone' => '+23480000000'.$i,
                'status' => 'approved',
                'approved_at' => now()->subDays(20 - $i),
                'submitted_at' => now()->subDays(22 - $i),
                'identity_verified_at' => now()->subDays(21 - $i),
            ])->save();

            $first = true;
            foreach ($skills as $skillName => $level) {
                $skill = Skill::where('name', $skillName)->first();
                if (! $skill) {
                    continue;
                }
                $attemptId = null;
                if ($level && ($assessment = Assessment::where('skill_id', $skill->id)->first())) {
                    $pct = [1 => 66, 2 => 72, 3 => 82, 4 => 90, 5 => 96][$level];
                    $attemptId = AssessmentAttempt::create([
                        'user_id' => $user->id, 'assessment_id' => $assessment->id, 'question_ids' => [], 'answers' => [],
                        'status' => 'passed', 'score' => $pct, 'max_score' => 100, 'percentage' => $pct, 'level_awarded' => $level,
                        'feedback' => 'Demo result.', 'started_at' => now()->subDays(15), 'expires_at' => now()->subDays(15)->addMinutes(20), 'submitted_at' => now()->subDays(15),
                    ])->id;
                }
                $profile->skills()->syncWithoutDetaching([$skill->id => [
                    'self_level' => $level ?? 3,
                    'years' => max(1, $years - 1),
                    'is_primary' => $first,
                    'verified_level' => $level,
                    'verified_score' => $level ? [1 => 66, 2 => 72, 3 => 82, 4 => 90, 5 => 96][$level] : null,
                    'verified_at' => $level ? now()->subDays(15) : null,
                    'verification_expires_at' => $level ? now()->addMonths(23) : null,
                ]]);
                if ($attemptId) {
                    $profile->profileSkills()->where('skill_id', $skill->id)->update(['assessment_attempt_id' => $attemptId]);
                }
                $first = false;
            }
            $profile->forceFill(['last_verified_at' => now()->subDays(15 - $i)])->save();

            if (! $profile->experiences()->exists()) {
                $profile->experiences()->create(['title' => $role, 'organization' => ['Paystack-style fintech', 'Logistics scale-up', 'Health-tech startup', 'Digital agency'][$i % 4], 'location' => $city, 'started_on' => now()->subYears(2), 'is_current' => true, 'description' => 'Shipped core product features end to end with a small cross-functional team.']);
                $profile->portfolio()->create(['title' => ['Merchant dashboard', 'Order tracking API', 'Claims analytics pipeline', 'Zero-downtime deploys'][$i % 4], 'role' => 'Lead '.$role, 'technologies' => implode(', ', array_keys($skills)), 'description' => 'A real-world project delivered for a growing business.', 'outcome' => 'Cut processing time by 40% and reduced support tickets.']);
            }
        }

        // Demo employer
        $hr = User::firstOrNew(['email' => 'hiring@demo.devhail.test']);
        $hr->forceFill(['name' => 'Ngozi (Demo HR)', 'password' => Hash::make('password'), 'is_hirer' => true, 'email_verified_at' => now(), 'terms_accepted_at' => now(), 'status' => 'active'])->save();
        $org = Organization::firstOrNew(['slug' => 'kudi-labs-demo']);
        $org->forceFill(['owner_id' => $hr->id, 'name' => 'Kudi Labs (Demo)', 'industry' => 'Fintech', 'size' => '11-50', 'city' => 'Lagos', 'about' => 'A demo fintech company building payments for small businesses.', 'website' => 'https://example.com', 'verified_at' => now(), 'unlock_credits' => 5])->save();
        $org->members()->syncWithoutDetaching([$hr->id => ['role' => 'owner']]);

        $jobs = [
            ['Senior React Engineer', 'senior', 'full_time', 'remote', 800000, 1200000, ['React', 'TypeScript']],
            ['Laravel Backend Developer', 'mid', 'full_time', 'hybrid', 500000, 800000, ['Laravel', 'SQL']],
            ['DevOps Contractor (3 months)', 'senior', 'contract', 'remote', 1000000, 1500000, ['Docker', 'CI/CD']],
        ];
        foreach ($jobs as [$title, $seniority, $type, $arr, $min, $max, $skills]) {
            $job = JobPost::firstOrNew(['slug' => Str::slug($title.' kudi labs demo')]);
            $job->forceFill([
                'organization_id' => $org->id, 'posted_by' => $hr->id, 'title' => $title, 'seniority' => $seniority, 'employment_type' => $type, 'work_arrangement' => $arr,
                'location' => 'Lagos, Nigeria', 'salary_min' => $min * 100, 'salary_max' => $max * 100, 'status' => 'open', 'deadline' => now()->addWeeks(4),
                'summary' => 'Join a small, senior team building payments infrastructure for SMEs.',
                'description' => "We’re looking for a {$title} to help us ship reliable, delightful products for small businesses across Nigeria.\n\nYou’ll work closely with product and design, own features end to end, and help shape our engineering culture.",
                'responsibilities' => "Own features from design to production\nWrite tests and review code thoughtfully\nCollaborate with product, design and support",
                'benefits' => "Remote-friendly with a Lagos hub\nHealth insurance\nLearning budget\nNew laptop",
            ])->save();
            $job->skills()->syncWithoutDetaching(Skill::whereIn('name', $skills)->pluck('id')->mapWithKeys(fn ($id) => [$id => ['is_required' => true]])->all());
        }

        // Demo client project
        $client = User::firstOrNew(['email' => 'client@demo.devhail.test']);
        $client->forceFill(['name' => 'Bola (Demo Client)', 'password' => Hash::make('password'), 'is_hirer' => true, 'email_verified_at' => now(), 'terms_accepted_at' => now(), 'status' => 'active'])->save();
        $projects = [
            ['Build a booking website for a Lagos salon', 150000000, 300000000, ['React', 'Laravel'], 'We need a simple, mobile-first website where customers can see services, prices and book appointments. Staff should get an email/SMS when someone books.'],
            ['Dockerise and deploy our Django app', 50000000, 120000000, ['Docker', 'CI/CD'], 'Our Django app runs on one VPS by hand. We want it containerised with a CI pipeline that deploys on merge to main, plus basic monitoring.'],
            ['Security review of our fintech API', 80000000, 200000000, ['Application security'], 'We need a defensive security review of our REST API (auth, access control, input handling) with a prioritised fix list. Authorised testing on staging only.'],
        ];
        foreach ($projects as [$title, $min, $max, $skills, $desc]) {
            $project = Project::firstOrNew(['slug' => Str::slug($title)]);
            $project->forceFill([
                'client_id' => $client->id, 'title' => $title, 'description' => $desc, 'budget_min' => $min, 'budget_max' => $max, 'currency' => 'NGN',
                'deliverables' => "Source code in a private repo\nDeployment + handover notes\nShort walkthrough video",
                'acceptance_criteria' => "Works on Chrome and Android 9+\nAll agreed features demoed on staging\nNo critical issues open",
                'status' => 'open', 'deadline' => now()->addWeeks(6), 'contract_type' => 'fixed',
            ])->save();
            $project->skills()->syncWithoutDetaching(Skill::whereIn('name', $skills)->pluck('id')->all());
        }
    }

    protected function availabilityLine(string $a): string
    {
        return ['open_to_work' => 'open to full-time roles', 'contract' => 'available for contract work', 'freelance' => 'taking freelance projects', 'unavailable' => 'heads-down on a project'][$a];
    }
}
