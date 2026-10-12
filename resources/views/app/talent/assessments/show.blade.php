@extends('layouts.app')
@section('title', $assessment->title)
@section('content')
<a href="{{ route('app.assessments.index') }}" class="small link">← Assessments</a>
<div class="split mt-2">
    <div class="stack" style="--stack:20px">
        <div class="card card-pop">
            <span class="sticker">{{ $assessment->skill->name }}</span>
            <h1 class="mt-2" style="font-size:2rem">{{ $assessment->title }}</h1>
            <p class="ink-2">{{ $assessment->description }}</p>
            <div class="grid-4 mt-2" style="gap:12px">
                <div class="kpi"><div class="label">Time</div><div class="value">{{ $assessment->duration_minutes }}m</div></div>
                <div class="kpi"><div class="label">Questions</div><div class="value">{{ $assessment->question_count }}</div></div>
                <div class="kpi"><div class="label">Pass mark</div><div class="value">{{ $assessment->pass_mark }}%</div></div>
                <div class="kpi"><div class="label">Retake after</div><div class="value">{{ $assessment->retake_days }}d</div></div>
            </div>
        </div>
        <div class="card">
            <h3>House rules 📜</h3>
            <div class="prose" style="font-size:.97rem">{!! nl2br(e($assessment->instructions)) !!}</div>
            @if($assessment->ai_policy)<div class="alert alert-info mt-2"><i data-lucide="bot"></i><div><strong>AI policy:</strong> {{ $assessment->ai_policy }}</div></div>@endif
            @if($assessment->requires_review)
                <div class="alert alert-warn mt-2"><i data-lucide="user-check"></i><div>This assessment includes a <strong>practical task</strong>. An expert reviewer checks it before your badge is issued — usually within 3 working days.</div></div>
            @endif
        </div>
        <div class="card card-soft">
            <h3>How levels are awarded</h3>
            <div class="grid-auto" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px">
                @foreach(\App\Support\Labels::LEVELS as $n => $l)<div><x-level :level="$n" /><p class="small muted mt-1 mb-0">{{ $l['blurb'] }}</p></div>@endforeach
            </div>
            <p class="small muted mt-2 mb-0">Levels come from your score band plus reviewer judgement for practical work. Badges are valid for {{ $assessment->validity_months }} months.</p>
        </div>
    </div>
    <aside class="sticky stack" style="--stack:16px">
        <div class="card">
            @if($open)
                <p>You have an attempt in progress.</p>
                <a href="{{ route('app.attempts.take', $open) }}" class="btn btn-accent btn-lg btn-block">Resume</a>
            @elseif($nextAt)
                <div class="alert alert-info"><i data-lucide="calendar-clock"></i><div>{{ $history->first()?->status === 'under_review' ? 'Your last attempt is with a reviewer. Hang tight!' : ($history->first()?->status === 'passed' ? 'You’re verified. You can reassess near expiry.' : 'Next attempt available '.$nextAt->format('j M Y').'.') }}</div></div>
            @else
                <form method="POST" action="{{ route('app.assessments.start', $assessment) }}">
                    @csrf
                    <label class="check mb-2"><input type="checkbox" name="agree" value="1" required> I’ll complete this myself, follow the AI policy, and understand the timer can’t be paused.</label>
                    <button class="btn btn-accent btn-lg btn-block" type="submit">Start now — {{ $assessment->duration_minutes }} min ⏱</button>
                </form>
                @if($freeLeft > 0)<p class="small muted center mt-1 mb-0">Free · {{ $freeLeft }} free {{ \Illuminate\Support\Str::plural('attempt', $freeLeft) }} left on your account</p>@endif
            @endif
        </div>
        @if($history->isNotEmpty())
            <div class="card"><h4>Your history</h4>
                @foreach($history as $h)<div class="row-between small mt-1"><span>{{ $h->created_at->format('j M Y') }}</span><x-status :status="$h->status" :label="$h->statusLabel()" /></div>@endforeach
            </div>
        @endif
    </aside>
</div>
@endsection
