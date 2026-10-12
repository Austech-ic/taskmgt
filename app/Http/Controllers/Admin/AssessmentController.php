<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Skill;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Copilot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssessmentController extends Controller
{
    public function index()
    {
        return view('admin.assessments.index', [
            'assessments' => Assessment::with('skill')->withCount(['questions', 'questions as active_count' => fn ($q) => $q->where('is_active', true), 'attempts', 'attempts as passed_count' => fn ($q) => $q->where('status', 'passed')])->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.assessments.form', ['assessment' => new Assessment(['duration_minutes' => 20, 'question_count' => 10, 'pass_mark' => 70, 'retake_days' => 14, 'validity_months' => 24, 'status' => 'draft']), 'skills' => Skill::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $assessment = new Assessment($this->validated($request));
        $assessment->slug = Str::slug($assessment->title).'-'.Str::lower(Str::random(4));
        $assessment->save();
        AuditLog::record('assessment.created', $assessment);

        return redirect()->route('admin.assessments.edit', $assessment)->with('success', 'Assessment created. Now add questions.');
    }

    public function edit(Assessment $assessment)
    {
        return view('admin.assessments.form', [
            'assessment' => $assessment,
            'skills' => Skill::orderBy('name')->get(),
            'questions' => $assessment->questions()->orderByDesc('is_active')->orderBy('id')->get()->each->makeVisible(['correct', 'explanation']),
            'aiEnabled' => app(Copilot::class)->enabled(),
        ]);
    }

    public function update(Request $request, Assessment $assessment)
    {
        $data = $this->validated($request);
        // Material changes to the test bump its version so results stay traceable.
        $material = collect(['pass_mark', 'question_count', 'duration_minutes', 'requires_review'])->contains(fn ($k) => (string) $assessment->$k !== (string) ($data[$k] ?? $assessment->$k));
        $assessment->fill($data);
        if ($material) {
            $assessment->version++;
        }
        $assessment->save();
        AuditLog::record('assessment.updated', $assessment, ['version' => $assessment->version]);

        if ($assessment->status === 'published' && $assessment->activeQuestions()->count() < $assessment->question_count) {
            return back()->with('warning', 'Saved — but there are fewer active questions than the question count. Candidates will get a shorter test.');
        }

        return back()->with('success', 'Assessment saved'.($material ? ' (version '.$assessment->version.')' : '').'.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'skill_id' => 'required|exists:skills,id',
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string|max:4000',
            'format' => 'required|in:knowledge,practical,mixed',
            'duration_minutes' => 'required|integer|between:5,240',
            'question_count' => 'required|integer|between:1,100',
            'pass_mark' => 'required|integer|between:1,100',
            'retake_days' => 'required|integer|between:0,365',
            'validity_months' => 'required|integer|between:1,60',
            'requires_review' => 'nullable|boolean',
            'practical_brief' => 'nullable|string|max:6000',
            'ai_policy' => 'nullable|string|max:1000',
            'status' => 'required|in:draft,published,retired',
        ]);
        $data['requires_review'] = $request->boolean('requires_review');

        return $data;
    }

    public function storeQuestion(Request $request, Assessment $assessment)
    {
        $assessment->questions()->create($this->questionData($request));
        AuditLog::record('question.created', $assessment);

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $question->update($this->questionData($request));
        AuditLog::record('question.updated', $question);

        return back()->with('success', 'Question updated.');
    }

    public function deleteQuestion(Question $question)
    {
        // Retire rather than delete if it has been served, so past attempts stay explainable.
        if ($question->exposure_count > 0) {
            $question->update(['is_active' => false]);

            return back()->with('success', 'Question retired (it has been served before, so it’s kept for audit).');
        }
        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    protected function questionData(Request $request): array
    {
        $data = $request->validate([
            'type' => 'required|in:single,multiple,short',
            'topic' => 'nullable|string|max:80',
            'body' => 'required|string|max:3000',
            'code_snippet' => 'nullable|string|max:4000',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string|max:500',
            'correct' => 'required|array|min:1',
            'correct.*' => 'required|string|max:300',
            'points' => 'required|integer|between:1,10',
            'difficulty' => 'required|integer|between:1,5',
            'explanation' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);
        $options = collect($data['options'] ?? [])->filter(fn ($t) => filled($t))->map(fn ($t, $k) => ['key' => (string) $k, 'text' => $t])->values()->all();
        if ($data['type'] !== 'short' && count($options) < 2) {
            abort(back()->withInput()->with('error', 'Choice questions need at least two options.'));
        }

        return [
            'type' => $data['type'],
            'topic' => $data['topic'] ?? null,
            'body' => $data['body'],
            'code_snippet' => $data['code_snippet'] ?? null,
            'options' => $data['type'] === 'short' ? null : $options,
            'correct' => array_values(array_filter($data['correct'], 'filled')),
            'points' => $data['points'],
            'difficulty' => $data['difficulty'],
            'explanation' => $data['explanation'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    public function aiDraft(Request $request, Assessment $assessment, Copilot $copilot)
    {
        $data = $request->validate(['topic' => 'required|string|max:120', 'difficulty' => 'required|integer|between:1,5', 'count' => 'required|integer|between:1,8']);
        try {
            $drafts = $copilot->draftQuestions($assessment->skill->name, $data['topic'], $data['difficulty'], $data['count']);
        } catch (AiUnavailable $e) {
            return back()->with('error', $e->getMessage());
        }
        foreach ($drafts['questions'] ?? [] as $q) {
            $assessment->questions()->create([
                'type' => 'single', 'topic' => $data['topic'], 'body' => $q['body'],
                'options' => collect($q['options'])->map(fn ($t, $k) => ['key' => $k, 'text' => $t])->values()->all(),
                'correct' => [$q['correct']], 'explanation' => $q['explanation'], 'difficulty' => $data['difficulty'],
                'points' => $data['difficulty'] >= 3 ? 2 : 1,
                'is_active' => false, // never served until a human reviews and activates it
                'source' => 'ai_draft',
            ]);
        }
        AuditLog::record('question.ai_drafted', $assessment, ['count' => count($drafts['questions'] ?? [])]);

        return back()->with('success', count($drafts['questions'] ?? []).' AI drafts added as INACTIVE. Review, fix and activate the good ones.');
    }
}
