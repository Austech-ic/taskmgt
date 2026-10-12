<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\Testimonial;
use App\Services\Ledger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Base content every Devhail install needs. Safe to run more than once. */
class PlatformSeeder extends Seeder
{
    protected const ASSESSMENT_SKILLS = [
        'javascript' => 'JavaScript',
        'react' => 'React',
        'python' => 'Python',
        'laravel' => 'Laravel',
        'sql' => 'SQL',
        'docker' => 'Docker',
        'ui-ux-design' => 'UI/UX design',
        'application-security' => 'Application security',
    ];

    public function run(): void
    {
        $this->taxonomy();
        $this->assessments();
        $this->plans();
        $this->pages();
        $this->faqs();
        $this->testimonials();
        $this->posts();

        $ledger = app(Ledger::class);
        foreach (array_keys(Ledger::ACCOUNTS) as $code) {
            $ledger->account($code, 'NGN');
        }
    }

    protected function taxonomy(): void
    {
        foreach (require __DIR__.'/data/taxonomy.php' as $i => [$name, $icon, $description, $skills]) {
            $category = SkillCategory::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'icon' => $icon, 'description' => $description, 'position' => $i]);
            foreach ($skills as $skill => $synonyms) {
                Skill::updateOrCreate(['slug' => Str::slug(str_replace(['/', '#', '.'], [' ', ' sharp', ' '], $skill))], [
                    'skill_category_id' => $category->id,
                    'name' => $skill,
                    'synonyms' => $synonyms ?: null,
                ]);
            }
        }
    }

    protected function assessments(): void
    {
        foreach (require __DIR__.'/data/assessments.php' as $key => $def) {
            $skill = Skill::where('name', self::ASSESSMENT_SKILLS[$key])->firstOrFail();
            $practical = ! empty($def['requires_review']);
            $assessment = Assessment::firstOrCreate(['slug' => $key], [
                'skill_id' => $skill->id,
                'title' => $def['title'],
                'description' => $def['description'],
                'instructions' => "Answer every question on your own. You can move between questions and your answers autosave. When the timer hits zero your attempt is submitted automatically.\n\nUsing documentation or AI tools during this test is not allowed — we want to see what you know. Switching away from the test tab is recorded and may send your attempt to a human reviewer.",
                'format' => $practical ? 'mixed' : 'knowledge',
                'duration_minutes' => $practical ? 60 : 20,
                'question_count' => min(10, count($def['questions'])),
                'pass_mark' => 70,
                'retake_days' => 14,
                'validity_months' => 24,
                'requires_review' => $practical,
                'practical_brief' => $def['practical_brief'] ?? null,
                'ai_policy' => 'No AI assistance during timed questions. For practical briefs, AI tools may help with production assets but your rationale must be your own and you must disclose how you used them.',
                'status' => 'published',
            ]);

            if ($assessment->questions()->exists()) {
                continue;
            }
            foreach ($def['questions'] as $q) {
                $assessment->questions()->create([
                    'type' => 'single',
                    'topic' => $q[0],
                    'body' => $q[1],
                    'options' => collect($q[2])->values()->map(fn ($text, $i) => ['key' => chr(97 + $i), 'text' => $text])->all(),
                    'correct' => [$q[3]],
                    'explanation' => $q[4],
                    'difficulty' => $q[5],
                    'code_snippet' => $q[6] ?? null,
                    'points' => $q[5] >= 3 ? 2 : 1,
                ]);
            }
        }
    }

    protected function plans(): void
    {
        $plans = [
            ['Pay as you go', 'payg', 'Dip in when you need one great hire.', 0, 0, 1, 1, ['Search verified talent for free', 'Send on-platform contact requests', 'Buy contact unlocks one at a time', 'Post 1 job'], false, false],
            ['Starter', 'starter', 'For founders hiring their first few devs.', 2500000, 10, 1, 3, ['10 contact unlocks / month', '1 recruiter seat', '3 active job posts', 'Shortlists & notes', 'AI talent search'], false, false],
            ['Growth', 'growth', 'For teams that are hiring every month.', 7500000, 40, 5, 15, ['40 contact unlocks / month', '5 recruiter seats', '15 active job posts', 'Shared shortlists & CSV export', 'AI job-post writer', 'Priority support'], true, false],
            ['Enterprise', 'enterprise', 'Private talent pools, custom assessments, SLAs.', 0, 0, 50, 100, ['Custom unlock volume', 'Unlimited seats', 'Custom assessment programmes', 'Managed recruitment & placement', 'Dedicated success manager'], false, true],
        ];
        foreach ($plans as $i => [$name, $slug, $tagline, $price, $unlocks, $seats, $jobs, $features, $featured, $custom]) {
            Plan::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'tagline' => $tagline, 'price_minor' => $price, 'currency' => 'NGN', 'interval' => $custom ? 'custom' : 'month',
                'contact_unlocks' => $unlocks, 'recruiter_seats' => $seats, 'active_jobs' => $jobs, 'features' => $features,
                'is_featured' => $featured, 'is_custom' => $custom, 'position' => $i,
            ]);
        }
    }

    protected function pages(): void
    {
        $pages = [
            ['About Devhail', 'about', 'header', "## We’re building the trust layer for tech talent.\n\nHiring tech talent shouldn’t feel like a coin toss, and getting hired shouldn’t depend on who you know. Devhail exists to fix both sides.\n\n**For professionals**, Devhail is where your skills get the receipts they deserve. Take hands-on assessments, earn verified badges, and get found by teams that care about what you can actually do.\n\n**For employers and clients**, Devhail is a shortcut to people who’ve already shown their work — with milestone payments and a reputation system that keeps everyone honest.\n\n### What we believe\n\n- **Proof beats polish.** A verified skill says more than a fancy CV.\n- **Fair is a feature.** Paying us never buys a higher score or a better badge.\n- **Talent is everywhere.** We started in Nigeria and we’re building for Africa and the world.\n\nProven skills. Trusted talent. Delivered projects. That’s the whole vibe."],
            ['How verification works', 'verification', 'footer', "## Skills are tested, not claimed\n\nEvery skill on a Devhail profile is one of four kinds of evidence, and we always label which is which:\n\n1. **Self-reported** — the professional says they have it. Useful, unverified.\n2. **Assessment-verified** — they passed a Devhail assessment for that skill. You’ll see the level, the score band and the date.\n3. **Credential-verified** — a certification we’ve checked.\n4. **Client-confirmed** — completed, reviewed work on Devhail.\n\n### Levels\n\n| Level | Meaning |\n|---|---|\n| 1 · Foundation | Understands basic concepts and terminology |\n| 2 · Applied | Solves standard tasks with limited guidance |\n| 3 · Professional | Handles typical production tasks independently |\n| 4 · Advanced | Owns complex problems and trade-offs |\n| 5 · Expert | Deep expertise in a defined specialty |\n\n### Integrity\n\nQuestions are rotated and randomised, answer keys never leave our servers, and scoring happens server-side. Automated signals (like leaving the test tab a lot) never auto-fail anyone — they send the attempt to a human reviewer. You can appeal any decision.\n\nA passed assessment is evidence of tested knowledge and skill. It isn’t a guarantee of every future project — that’s what delivery history and reviews are for."],
            ['Fees & payments', 'fees', 'footer', "## Simple, upfront fees\n\n- **Professionals:** free to join, free profile, free starter assessments.\n- **Marketplace commission:** a percentage of each eligible milestone payment processed through Devhail, shown before anyone accepts a contract. It’s calculated per milestone as money is released — never charged upfront on the whole contract.\n- **Employers:** search is free. Unlock contact details with a subscription allowance or pay-per-contact credits.\n\n### How milestone payments work\n\n1. Client and professional agree scope, milestones and acceptance criteria.\n2. The client funds a milestone through our licensed payment partner. It only shows as **funded** after the provider confirms the payment.\n3. The professional delivers; the client approves or requests a revision against the agreed criteria.\n4. On approval, the milestone is released to the professional’s Devhail earnings, minus commission.\n5. Disputes pause the money and go to a neutral Devhail officer.\n\nDirectly contacting a candidate you unlocked never triggers a project commission — recruitment fees and marketplace commission are separate."],
            ['Terms of service', 'terms', 'footer', "## Terms of service\n\n_Last updated: ".now()->format('j F Y')."_\n\nThese starter terms are a template. Have them reviewed by a qualified lawyer before launch.\n\n1. **Accounts.** You must give accurate information and keep your login secure. One person, one professional profile.\n2. **Assessments.** Complete assessments yourself under the published rules. Attempts that break the rules can be voided after human review, with a right of appeal.\n3. **Marketplace.** Contracts are between the client and the professional. Devhail provides the platform, payment workflow through licensed providers, and dispute resolution.\n4. **Fees.** Commission and other fees are shown before you commit and are deducted as described on the Fees page.\n5. **Conduct.** No harassment, discrimination, fraud, fake reviews or off-platform payment pressure for marketplace contracts.\n6. **Content.** You keep ownership of your content and grant Devhail a licence to display it on the platform.\n7. **Suspension.** We may suspend accounts that break these terms, with notice and an appeal route where possible.\n8. **Liability.** Devhail is provided as-is to the extent permitted by law."],
            ['Privacy policy', 'privacy', 'footer', "## Privacy policy\n\n_Last updated: ".now()->format('j F Y')."_\n\nThis template is a starting point and should be reviewed against the Nigeria Data Protection Act 2023 and NDPC guidance before launch.\n\n- **What we collect:** account details, profile information you choose to add, assessment attempts, messages, and transaction records.\n- **Why:** to run your account, verify skills, match talent with employers, process payments and keep the platform safe.\n- **Contact details:** your phone number and private email are never shown publicly. Employers only see them after an authorised unlock, and only if you allow direct contact.\n- **Your controls:** hide your profile, hide your rate, turn off direct contact, download or delete your data by contacting us.\n- **Security:** encryption in transit, hashed passwords, private file storage and audit logs for sensitive actions.\n- **AI features:** when you use an AI helper, the text you provide is sent to our AI provider to generate a draft. We don’t use AI to decide assessment outcomes."],
            ['AI at Devhail', 'ai', 'footer', "## How we use AI (and how we don’t)\n\nDevhail has AI helpers to save you time:\n\n- ✍️ **Profile glow-up** drafts your headline and bio from facts you provide.\n- 🧭 **Smart talent search** turns “senior React dev in Lagos, open to remote” into filters.\n- 📄 **Job & project writers** turn rough notes into clear, inclusive briefs with milestones.\n- 💬 **Hail**, our help assistant, answers questions about how Devhail works.\n\nWhat AI **never** does here: decide whether you pass an assessment, change a score, rank people by anything other than evidence, or approve payments. Humans own those decisions.\n\nAI drafts can be wrong — always review before you publish."],
        ];
        foreach ($pages as $i => [$title, $slug, $placement, $body]) {
            Page::updateOrCreate(['slug' => $slug], ['title' => $title, 'placement' => $placement, 'body' => $body, 'position' => $i, 'meta_description' => Str::limit(strip_tags($body), 150)]);
        }
    }

    protected function faqs(): void
    {
        $faqs = [
            ['talent', 'Is Devhail free for professionals?', 'Yes. Your profile, portfolio and starter assessments are free. On marketplace projects, a commission is deducted from each milestone payment — the exact rate is shown before you accept any contract.'],
            ['talent', 'How do I get a verified badge?', 'Claim a skill on your profile, then pass its Devhail assessment. Some skills include a practical task reviewed by a human expert. Your badge shows the level, score band and date.'],
            ['talent', 'What if I don’t pass?', 'No drama. You get feedback on strong and weak areas and can retake after the cooldown period (usually 14 days). The skill just stays unverified until then.'],
            ['talent', 'Who can see my phone number and email?', 'Nobody, by default. Employers only see contact details after an authorised unlock — and only if you’ve allowed direct contact. Otherwise they reach you through Devhail messages.'],
            ['talent', 'Can I use AI during assessments?', 'Not during timed questions. For practical briefs, check the AI policy on that assessment — usually you can use tools for production assets but the reasoning must be yours and disclosed.'],
            ['hirers', 'How much does it cost to hire?', 'Searching and viewing profiles is free. To see a candidate’s contact details you use a subscription allowance or buy pay-per-contact credits. Marketplace projects carry a commission on milestone payments.'],
            ['hirers', 'How do milestone payments protect me?', 'You fund one milestone at a time through our licensed payment partner. Money is released only when you approve the deliverable against the agreed criteria — or after a dispute decision.'],
            ['hirers', 'Can I pay to rank higher or boost a candidate’s score?', 'No. Search ranking is based on verified evidence and delivery history. Payments never change scores, levels or badges.'],
            ['everyone', 'What happens in a dispute?', 'The disputed amount is paused and a neutral Devhail officer reviews the contract, deliverables and messages, then decides to release, refund or split. Every decision is documented.'],
            ['everyone', 'Which countries do you support?', 'We’re launching in Nigeria with naira payments first, then expanding across Africa and to international remote work.'],
        ];
        foreach ($faqs as $i => [$audience, $q, $a]) {
            Faq::updateOrCreate(['question' => $q], ['audience' => $audience, 'answer' => $a, 'position' => $i]);
        }
    }

    protected function testimonials(): void
    {
        // Placeholder copy for a fresh install — replace with real customer quotes in Admin → CMS → Testimonials.
        $items = [
            ['Sample quote · Talent', 'Frontend engineer', 'I sent one Devhail link instead of five portfolio PDFs. The verified React badge did the talking.'],
            ['Sample quote · Hiring manager', 'Fintech startup', 'We stopped running take-home tests for the basics. Shortlisting went from two weeks to two days.'],
            ['Sample quote · Client', 'SME founder', 'Milestones made it easy to trust a freelancer I’d never met. Pay when it’s done, done right.'],
        ];
        foreach ($items as $i => [$name, $role, $quote]) {
            Testimonial::updateOrCreate(['name' => $name], ['role' => $role, 'quote' => $quote, 'position' => $i]);
        }
    }

    protected function posts(): void
    {
        $posts = [
            ['Receipts, not résumés: why we test skills', 'Product', '🧾', "Anyone can type “React” on a CV. Devhail asks a different question: **can you show it?**\n\n## The problem with claims\n\nRecruiters skim hundreds of profiles that all say the same thing. Great engineers get lost in the noise, and hiring teams burn weeks on screening calls.\n\n## Our approach\n\n- Modular assessments tied to specific skills — not one giant exam.\n- Practical work for hands-on roles, reviewed by humans.\n- Clear labels: self-reported vs verified vs client-confirmed.\n\n## What it means for you\n\nIf you’re talent: your best work finally gets evidence. If you’re hiring: you start every conversation with proof.\n\nReady? [Create your profile](/join) and take your first assessment free."],
            ['5 profile upgrades that get you shortlisted', 'Career', '🚀', "Small tweaks, big difference.\n\n## 1. Lead with outcomes\n\n“Built a checkout that cut drop-off by 18%” beats “responsible for frontend”.\n\n## 2. Verify your primary skill first\n\nEmployers filter by verified level. One badge moves you into far more searches.\n\n## 3. Show one project deeply\n\nRole, stack, your contribution, the result. Depth > a wall of links.\n\n## 4. Be honest about availability\n\n“Open to contract” vs “freelance only” saves everyone time.\n\n## 5. Keep it fresh\n\nProfiles verified recently rank higher. Reassess when your badge nears expiry."],
            ['Milestones 101 for first-time clients', 'Hiring', '🧱', "Hiring a freelancer for the first time? Milestones are your best friend.\n\n## Break it down\n\nSplit the project into 2–5 chunks that each produce something you can check.\n\n## Write acceptance criteria\n\n“Login works with email + OTP on Android 9+” is testable. “Make it nice” is not.\n\n## Fund one at a time\n\nYou only fund the current milestone. Money moves to the professional when you approve.\n\n## Use the dispute button wisely\n\nIf something’s off, talk first. If you’re stuck, a neutral Devhail officer can step in."],
        ];
        foreach ($posts as $i => [$title, $category, $emoji, $body]) {
            Post::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title, 'category' => $category, 'emoji' => $emoji, 'body' => $body,
                'excerpt' => Str::limit(trim(strip_tags(str_replace(['#', '*'], '', $body))), 150),
                'is_published' => true, 'published_at' => now()->subDays(3 * ($i + 1)), 'read_minutes' => 3,
            ]);
        }
    }
}
