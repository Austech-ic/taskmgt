<?php

namespace App\Services\Ai;

use App\Models\Faq;
use App\Models\Skill;
use App\Support\Labels;

/**
 * Devhail's product-level AI features. Each method builds a focused prompt
 * and delegates to AiService.
 */
class Copilot
{
    public function __construct(protected AiService $ai) {}

    public function enabled(): bool
    {
        return $this->ai->enabled();
    }

    public function profileBio(array $facts): array
    {
        return $this->ai->askJson('profile_bio',
            'Write a professional headline (max 90 characters) and a first-person bio (90–150 words, 2 short paragraphs) for a tech professional profile. Use only the facts given. Lead with what they build and for whom.',
            "Facts about the professional:\n".$this->lines($facts),
            ['type' => 'object', 'additionalProperties' => false, 'required' => ['headline', 'bio'], 'properties' => [
                'headline' => ['type' => 'string'],
                'bio' => ['type' => 'string'],
            ]],
            1200
        );
    }

    public function jobDescription(array $brief): array
    {
        return $this->ai->askJson('job_description',
            'Draft an inclusive, specific job post for a tech role. Avoid gendered language, unnecessary degree requirements and "rockstar/ninja" clichés. Responsibilities and benefits are newline-separated bullet lines without bullet characters.',
            "Hiring brief:\n".$this->lines($brief),
            ['type' => 'object', 'additionalProperties' => false, 'required' => ['summary', 'description', 'responsibilities', 'benefits'], 'properties' => [
                'summary' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'responsibilities' => ['type' => 'string'],
                'benefits' => ['type' => 'string'],
            ]],
            2500
        );
    }

    public function projectScope(array $brief): array
    {
        return $this->ai->askJson('project_scope',
            'Turn a rough client idea into a clear freelance project brief: a description, concrete deliverables, testable acceptance criteria, and 2–5 milestones with a suggested share of budget (percent integers summing to 100). Deliverables and acceptance criteria are newline-separated lines without bullet characters.',
            "Client's idea:\n".$this->lines($brief),
            ['type' => 'object', 'additionalProperties' => false, 'required' => ['description', 'deliverables', 'acceptance_criteria', 'milestones'], 'properties' => [
                'description' => ['type' => 'string'],
                'deliverables' => ['type' => 'string'],
                'acceptance_criteria' => ['type' => 'string'],
                'milestones' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'percent'], 'properties' => [
                    'title' => ['type' => 'string'],
                    'percent' => ['type' => 'integer'],
                ]]],
            ]],
            2500
        );
    }

    public function proposal(array $context): string
    {
        return $this->ai->ask('proposal',
            'Draft a freelance proposal cover letter (120–200 words) from the professional to the client. Show understanding of the problem, outline the approach in 3 short steps, and reference only the evidence provided. End with one clarifying question. Plain text only.',
            $this->lines($context),
            1500
        );
    }

    /** Turn a natural-language talent query into Devhail search filters. */
    public function parseTalentQuery(string $query): array
    {
        $skills = Skill::where('is_active', true)->pluck('name')->implode(', ');

        return $this->ai->askJson('talent_search',
            "Convert a recruiter's plain-English request into search filters for Devhail's talent directory.\nKnown skills (use exact names; pick only those clearly implied): {$skills}.\nLevels: 1 Foundation, 2 Applied, 3 Professional, 4 Advanced, 5 Expert — map junior≈2, mid≈3, senior≈4, principal/expert≈5. Leave fields empty when not implied.",
            "Recruiter request: {$query}",
            ['type' => 'object', 'additionalProperties' => false, 'required' => ['keywords', 'skills', 'min_level', 'location', 'availability', 'arrangement', 'min_years', 'summary'], 'properties' => [
                'keywords' => ['type' => 'string'],
                'skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                'min_level' => ['type' => 'integer'],
                'location' => ['type' => 'string'],
                'availability' => ['type' => 'string', 'enum' => array_merge([''], array_keys(Labels::AVAILABILITY))],
                'arrangement' => ['type' => 'string', 'enum' => array_merge([''], array_keys(Labels::ARRANGEMENTS))],
                'min_years' => ['type' => 'integer'],
                'summary' => ['type' => 'string'],
            ]],
            800
        );
    }

    /** Draft questions for a reviewer to edit. Drafts are saved inactive and never served until a human activates them. */
    public function draftQuestions(string $skill, string $topic, int $difficulty, int $count): array
    {
        return $this->ai->askJson('question_drafts',
            'You are an assessment author. Write ORIGINAL single-answer multiple-choice questions that test applied, practical understanding (scenarios, debugging, trade-offs), not trivia. Do not copy from any certification exam. Each has 4 options keyed a–d, exactly one correct key, and a one-sentence explanation.',
            "Skill: {$skill}\nTopic: {$topic}\nDifficulty (1-5): {$difficulty}\nNumber of questions: {$count}",
            ['type' => 'object', 'additionalProperties' => false, 'required' => ['questions'], 'properties' => [
                'questions' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['body', 'options', 'correct', 'explanation'], 'properties' => [
                    'body' => ['type' => 'string'],
                    'options' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['a', 'b', 'c', 'd'], 'properties' => [
                        'a' => ['type' => 'string'], 'b' => ['type' => 'string'], 'c' => ['type' => 'string'], 'd' => ['type' => 'string'],
                    ]],
                    'correct' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']],
                    'explanation' => ['type' => 'string'],
                ]]],
            ]],
            4000
        );
    }

    public function blogDraft(string $topic, string $audience): string
    {
        return $this->ai->ask('blog_draft',
            'Write a blog post in Markdown for the Devhail blog (500–800 words): a hook intro, 3–5 scannable sections with ## headings, practical takeaways, and a closing call-to-action to join Devhail. No title line.',
            "Topic: {$topic}\nAudience: {$audience}",
            3500
        );
    }

    /** Help-desk assistant grounded in the site's FAQs. */
    public function assistant(string $question, array $history = []): string
    {
        $faqs = Faq::where('is_published', true)->orderBy('position')->get()
            ->map(fn ($f) => "Q: {$f->question}\nA: {$f->answer}")->implode("\n\n");
        $site = setting('site_name');
        $commission = rtrim(rtrim(number_format(setting('commission_bps') / 100, 2), '0'), '.');

        $transcript = collect($history)->take(-6)->map(fn ($m) => (($m['role'] ?? '') === 'assistant' ? 'Assistant' : 'User').': '.mb_substr((string) ($m['content'] ?? ''), 0, 600))->implode("\n");

        return $this->ai->ask('assistant',
            "You are Hail, the {$site} help assistant. Answer questions about how {$site} works using the knowledge base below. Keep answers under 120 words. If the answer isn't in the knowledge base, say you're not sure and suggest the contact page. Never promise outcomes like guaranteed jobs or passes.\nKey facts: marketplace commission is {$commission}% of eligible project payments; skills are verified only by passing assessments; payments are confirmed by the payment provider before milestones count as funded.\n\nKnowledge base:\n{$faqs}",
            ($transcript ? "Conversation so far:\n{$transcript}\n\n" : '')."User: {$question}",
            800
        );
    }

    protected function lines(array $data): string
    {
        return collect($data)->filter(fn ($v) => filled($v))->map(fn ($v, $k) => '- '.str_replace('_', ' ', $k).': '.(is_array($v) ? implode(', ', $v) : $v))->implode("\n");
    }
}
