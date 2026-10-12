@extends('layouts.admin')
@section('title', 'Review attempt')
@section('content')
<a href="{{ route('admin.attempts.index') }}" class="small link">← Queue</a>
<x-page-head :title="$attempt->user->name.' · '.$attempt->assessment->title" :subtitle="'Attempt #'.$attempt->id.' · version '.$attempt->assessment_version.' · '.$attempt->statusLabel()" />
<div class="split">
    <div class="stack" style="--stack:20px">
        <div class="kpis" style="grid-template-columns:repeat(4,minmax(0,1fr))">
            <div class="kpi"><div class="label">Score</div><div class="value">{{ $attempt->percentage }}%</div><div class="sub">{{ $attempt->score }}/{{ $attempt->max_score }} pts</div></div>
            <div class="kpi"><div class="label">Pass mark</div><div class="value">{{ $attempt->assessment->pass_mark }}%</div></div>
            <div class="kpi"><div class="label">Time used</div><div class="value">{{ $attempt->started_at && $attempt->submitted_at ? $attempt->started_at->diffInMinutes($attempt->submitted_at) : '—' }}m</div><div class="sub">of {{ $attempt->assessment->duration_minutes }}m</div></div>
            <div class="kpi"><div class="label">Tab switches</div><div class="value">{{ $attempt->focus_losses }}</div></div>
        </div>
        @if($attempt->integrity_flags)<div class="alert alert-warn"><i data-lucide="flag"></i><div><strong>Integrity signals:</strong> {{ collect($attempt->integrity_flags)->map(fn ($f) => str_replace('_', ' ', $f))->implode(', ') }}. Consider them alongside the work — they can have innocent explanations.</div></div>@endif
        @if($attempt->practical_submission || $attempt->practical_url)
            <div class="card card-pop"><h3>Practical submission</h3>
                @if($attempt->practical_url)<p><a class="link" href="{{ $attempt->practical_url }}" target="_blank" rel="noopener nofollow">{{ $attempt->practical_url }} ↗</a></p>@endif
                <div style="white-space:pre-wrap">{{ $attempt->practical_submission }}</div>
                @if($attempt->assessment->practical_brief)<details class="mt-2"><summary class="small strong" style="cursor:pointer">Brief</summary><p class="small" style="white-space:pre-wrap">{{ $attempt->assessment->practical_brief }}</p></details>@endif
            </div>
        @endif
        <div class="card"><h3>Answers</h3>
            @foreach($questions as $i => $q)
                @php($given = ($attempt->answers ?? [])[$q->id] ?? null)
                @php($earned = $q->score($given))
                <div class="mt-2" style="padding:12px;border:1px solid var(--line);border-radius:12px">
                    <div class="row-between small"><strong>{{ $i + 1 }}. {{ $q->topic }}</strong><span class="badge {{ $earned ? 'badge-green' : 'badge-red' }}">{{ $earned }}/{{ $q->points }}</span></div>
                    <p class="small mt-1 mb-1">{{ $q->body }}</p>
                    <div class="small">Given: <strong>{{ is_array($given) ? implode(', ', $given) : ($given ?? '—') }}</strong> · Correct: <strong>{{ implode(', ', $q->correct) }}</strong></div>
                </div>
            @endforeach
        </div>
    </div>
    <aside class="sticky">
        @if($attempt->status === 'under_review')
            <form method="POST" action="{{ route('admin.attempts.decide', $attempt) }}" class="card card-pop">
                @csrf
                <h3>Decision</h3>
                <div class="radio-cards mb-2" style="grid-template-columns:1fr 1fr">
                    <label class="radio-card"><input type="radio" name="decision" value="pass" required><div class="card center"><strong>✅ Pass</strong></div></label>
                    <label class="radio-card"><input type="radio" name="decision" value="fail"><div class="card center"><strong>🌱 Not yet</strong></div></label>
                </div>
                <div class="field"><label>Level (if passing)</label><select class="select" name="level"><option value="">Auto from score (L{{ app(\App\Services\AssessmentService::class)->levelFor($attempt->percentage) }})</option>@foreach(\App\Support\Labels::LEVELS as $n => $l)<option value="{{ $n }}">{{ $n }} · {{ $l['name'] }}</option>@endforeach</select></div>
                <div class="field"><label>Feedback for the candidate</label><textarea class="textarea" name="notes" rows="5" required minlength="10" placeholder="What was strong, what to improve. The candidate sees this."></textarea></div>
                <button class="btn btn-primary btn-block">Record decision</button>
            </form>
        @else
            <div class="card"><h3>Decided</h3><p class="small">By {{ $attempt->reviewer?->name ?? 'auto-scoring' }} {{ $attempt->reviewed_at?->diffForHumans() }}</p>@if($attempt->reviewer_notes)<p class="small">{{ $attempt->reviewer_notes }}</p>@endif</div>
        @endif
        @if($attempt->user->profile)<a class="btn btn-block mt-2" href="{{ route('admin.talent.show', $attempt->user->profile) }}">Open profile</a>@endif
    </aside>
</div>
@endsection
