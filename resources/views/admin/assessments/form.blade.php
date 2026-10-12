@extends('layouts.admin')
@section('title', $assessment->exists ? $assessment->title : 'New assessment')
@section('content')
<a href="{{ route('admin.assessments.index') }}" class="small link">← Assessments</a>
<x-page-head :title="$assessment->exists ? $assessment->title : 'New assessment'" :subtitle="$assessment->exists ? 'Version '.$assessment->version.' · changing pass mark, length or review mode bumps the version.' : 'Create the shell, then add questions.'" />
<div class="split">
    <div class="stack" style="--stack:20px">
        @if($assessment->exists)
            <div class="card">
                <div class="card-head"><h3>Question bank ({{ $questions->where('is_active', true)->count() }} active / {{ $questions->count() }})</h3></div>
                @foreach($questions as $q)
                    <details class="mb-1" style="border:1px solid var(--line);border-radius:12px;padding:10px 14px;{{ $q->is_active ? '' : 'background:var(--paper-2)' }}">
                        <summary style="cursor:pointer" class="row-between"><span class="small"><strong>{{ \Illuminate\Support\Str::limit($q->body, 90) }}</strong></span>
                            <span class="row gap-xs">@if($q->source === 'ai_draft')<span class="badge badge-violet">AI draft</span>@endif<span class="badge {{ $q->is_active ? 'badge-green' : '' }}">{{ $q->is_active ? 'active' : 'inactive' }}</span><span class="badge">served {{ $q->exposure_count }}×</span></span></summary>
                        <div class="mt-2">@include('admin.assessments._question', ['q' => $q, 'action' => route('admin.questions.update', $q), 'method' => 'PUT'])</div>
                        <form method="POST" action="{{ route('admin.questions.destroy', $q) }}" data-confirm="Delete / retire this question?" class="mt-1">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">{{ $q->exposure_count ? 'Retire' : 'Delete' }}</button></form>
                    </details>
                @endforeach
                <details class="mt-2 card card-soft" open><summary class="strong" style="cursor:pointer">+ Add question</summary><div class="mt-2">@include('admin.assessments._question', ['q' => null, 'action' => route('admin.questions.store', $assessment), 'method' => 'POST'])</div></details>
            </div>
            <form method="POST" action="{{ route('admin.assessments.ai', $assessment) }}" class="ai-panel">
                @csrf
                <div class="row mb-1"><span class="sparkle"><i data-lucide="sparkles"></i></span><strong>Draft questions with AI</strong>@unless($aiEnabled)<span class="badge">AI off</span>@endunless</div>
                <p class="small muted">Drafts are saved <strong>inactive</strong> and never served until a subject-matter reviewer edits and activates them. Original questions only — never copied from certification exams.</p>
                <div class="row" style="flex-wrap:nowrap"><input class="input" name="topic" placeholder="Topic e.g. async/await, indexing, XSS" required><select class="select" name="difficulty" style="width:auto">@for($i = 1; $i <= 5; $i++)<option value="{{ $i }}" @selected($i === 3)>Difficulty {{ $i }}</option>@endfor</select><select class="select" name="count" style="width:auto">@foreach([3, 5, 8] as $n)<option>{{ $n }}</option>@endforeach</select><button class="btn ai-btn">Draft</button></div>
            </form>
        @endif
    </div>
    <aside>
        <form method="POST" action="{{ $assessment->exists ? route('admin.assessments.update', $assessment) : route('admin.assessments.store') }}" class="card card-pop">
            @csrf @if($assessment->exists) @method('PUT') @endif
            <div class="field"><label>Skill</label><select class="select" name="skill_id" required>@foreach($skills as $s)<option value="{{ $s->id }}" @selected($assessment->skill_id === $s->id)>{{ $s->name }}</option>@endforeach</select></div>
            <div class="field"><label>Title</label><input class="input" name="title" value="{{ old('title', $assessment->title) }}" required></div>
            <div class="field"><label>Description</label><textarea class="textarea" name="description" rows="3">{{ old('description', $assessment->description) }}</textarea></div>
            <div class="form-grid">
                <div class="field"><label>Format</label><select class="select" name="format">@foreach(['knowledge' => 'Knowledge', 'practical' => 'Practical', 'mixed' => 'Mixed'] as $k => $v)<option value="{{ $k }}" @selected($assessment->format === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label>Status</label><select class="select" name="status">@foreach(['draft', 'published', 'retired'] as $s)<option @selected($assessment->status === $s)>{{ $s }}</option>@endforeach</select></div>
                <div class="field"><label>Minutes</label><input type="number" class="input" name="duration_minutes" value="{{ $assessment->duration_minutes }}"></div>
                <div class="field"><label>Questions served</label><input type="number" class="input" name="question_count" value="{{ $assessment->question_count }}"></div>
                <div class="field"><label>Pass mark %</label><input type="number" class="input" name="pass_mark" value="{{ $assessment->pass_mark }}"></div>
                <div class="field"><label>Retake after (days)</label><input type="number" class="input" name="retake_days" value="{{ $assessment->retake_days }}"></div>
                <div class="field span-2"><label>Badge valid for (months)</label><input type="number" class="input" name="validity_months" value="{{ $assessment->validity_months }}"></div>
            </div>
            <label class="check mb-2"><input type="checkbox" name="requires_review" value="1" @checked($assessment->requires_review)> Human review required (practical work)</label>
            <div class="field"><label>Practical brief</label><textarea class="textarea" name="practical_brief" rows="4">{{ old('practical_brief', $assessment->practical_brief) }}</textarea></div>
            <div class="field"><label>Instructions</label><textarea class="textarea" name="instructions" rows="4">{{ old('instructions', $assessment->instructions) }}</textarea></div>
            <div class="field"><label>AI-use policy</label><textarea class="textarea" name="ai_policy" rows="3">{{ old('ai_policy', $assessment->ai_policy) }}</textarea></div>
            <button class="btn btn-primary btn-block">Save</button>
        </form>
    </aside>
</div>
@endsection
