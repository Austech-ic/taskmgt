@extends('layouts.app')
@section('title', 'Result')
@section('content')
<a href="{{ route('app.assessments.index') }}" class="small link">← Assessments</a>
<div class="split mt-2">
    <div class="stack" style="--stack:20px">
        <div class="card {{ $attempt->status === 'passed' ? 'card-lime' : 'card-pop' }}" style="text-align:center;padding:40px 24px">
            <div style="font-size:3.4rem">{{ ['passed' => '🏆', 'not_passed' => '🌱', 'under_review' => '🔍', 'voided' => '⛔', 'submitted' => '⏳'][$attempt->status] ?? '✨' }}</div>
            <h1 style="font-size:2.2rem" class="mt-1">
                @switch($attempt->status)
                    @case('passed') You passed. Verified! @break
                    @case('not_passed') Not yet — but you’re close. @break
                    @case('under_review') Submitted for expert review @break
                    @default {{ $attempt->statusLabel() }}
                @endswitch
            </h1>
            <p class="mb-2">{{ $attempt->assessment->skill->name }} · {{ $attempt->submitted_at?->format('j M Y, g:ia') }}</p>
            <div class="row" style="justify-content:center;gap:24px">
                <div><div class="stat-big">{{ $attempt->percentage }}%</div><div class="small">knowledge score (pass: {{ $attempt->assessment->pass_mark }}%)</div></div>
                @if($attempt->level_awarded)<div><div class="stat-big">L{{ $attempt->level_awarded }}</div><div class="small">{{ level_name($attempt->level_awarded) }}</div></div>@endif
            </div>
        </div>
        @if($attempt->status === 'under_review')
            <div class="alert alert-info"><i data-lucide="user-check"></i><div>A human reviewer will check your submission{{ $attempt->assessment->requires_review ? ', including the practical task' : '' }}. We’ll notify you when there’s a decision — usually within 3 working days. Automated checks never fail anyone on their own.</div></div>
        @endif
        @if($attempt->feedback)
            <div class="card"><h3>Feedback</h3><p class="mb-0">{{ $attempt->feedback }}</p></div>
        @endif
        @if($attempt->component_scores)
            <div class="card">
                <h3>Breakdown by topic</h3>
                @foreach($attempt->component_scores as $topic => $s)
                    @php($pct = $s['max'] ? round($s['earned'] / $s['max'] * 100) : 0)
                    <div class="mt-2"><div class="row-between small"><strong>{{ $topic }}</strong><span>{{ $pct }}%</span></div><div class="meter {{ $pct >= 80 ? 'green' : '' }} mt-1"><i style="width:{{ $pct }}%"></i></div></div>
                @endforeach
            </div>
        @endif
        @if($attempt->reviewer_notes && in_array($attempt->status, ['passed', 'not_passed']))
            <div class="card card-soft"><h3>Reviewer notes</h3><p class="mb-0">{{ $attempt->reviewer_notes }}</p></div>
        @endif
    </div>
    <aside class="sticky stack" style="--stack:16px">
        @if($attempt->status === 'passed')
            <div class="card"><h3>Flex it 💅</h3><p class="small muted">Your badge is live on your public profile.</p><a class="btn btn-primary btn-block" href="{{ route('talent.show', auth()->user()->profile) }}">View my profile</a>
                <button class="btn btn-block mt-1" data-copy="I just verified my {{ $attempt->assessment->skill->name }} skills on {{ setting('site_name') }} (Level {{ $attempt->level_awarded }}) 🚀 {{ route('talent.show', auth()->user()->profile) }}"><i data-lucide="share-2"></i> Copy share text</button></div>
        @elseif($attempt->status === 'not_passed')
            <div class="card"><h3>Run it back</h3><p class="small muted">You can retake from {{ ($attempt->submitted_at ?? $attempt->updated_at)->copy()->addDays($attempt->assessment->retake_days)->format('j M Y') }}. Focus on the weaker topics first.</p><a class="btn btn-block" href="{{ route('app.dashboard') }}">Back to dashboard</a></div>
        @endif
        <div class="card card-soft small"><strong>Something feel off?</strong><p class="muted mt-1 mb-0">You can appeal any result. <a class="link" href="{{ route('contact', ['topic' => 'talent']) }}">Contact us</a> with your attempt ID #{{ $attempt->id }}.</p></div>
    </aside>
</div>
@endsection
