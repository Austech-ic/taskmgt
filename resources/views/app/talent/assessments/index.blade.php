@extends('layouts.app')
@section('title', 'Assessments')
@section('content')
<x-page-head title="Assessments" subtitle="Pass a skill’s assessment to turn it into a verified badge. Your claimed skills are listed first." />
<div class="grid-3 mb-4">
    @foreach($assessments as $a)
        <div class="card card-hover" style="display:flex;flex-direction:column;gap:10px">
            <div class="row-between">
                <span class="icon-tile sm {{ $a->claimed?->isVerified() ? '' : 'lime' }}" @if($a->claimed?->isVerified()) style="background:var(--green-soft);color:var(--green)" @endif><i data-lucide="badge-check"></i></span>
                @if($a->claimed?->isVerified())<x-level :level="$a->claimed->verified_level" />@elseif($a->claimed?->is_primary)<span class="badge badge-ink">Primary skill</span>@elseif($a->claimed)<span class="badge badge-violet">Claimed</span>@endif
            </div>
            <h3 class="mb-0">{{ $a->skill->name }}</h3>
            <p class="small muted mb-0 clamp-2">{{ $a->description }}</p>
            <div class="row small muted gap-lg"><span>⏱ {{ $a->duration_minutes }}m</span><span>🎯 {{ $a->pass_mark }}%</span>@if($a->requires_review)<span>👀 reviewed</span>@endif</div>
            <div style="margin-top:auto">
                @if($a->last_attempt && $a->last_attempt->status === 'in_progress')
                    <a href="{{ route('app.attempts.take', $a->last_attempt) }}" class="btn btn-accent btn-sm btn-block">Resume attempt</a>
                @elseif($a->claimed?->isVerified())
                    <a href="{{ route('app.assessments.show', $a) }}" class="btn btn-sm btn-block">View details</a>
                @elseif($a->next_at)
                    <button class="btn btn-sm btn-block" disabled>{{ $a->last_attempt?->status === 'under_review' ? 'Under review' : 'Retake from '.$a->next_at->format('j M') }}</button>
                @else
                    <a href="{{ route('app.assessments.show', $a) }}" class="btn btn-primary btn-sm btn-block">Start</a>
                @endif
            </div>
        </div>
    @endforeach
</div>
<h2 style="font-size:1.4rem">Your attempts</h2>
@if($attempts->isEmpty())
    <x-empty icon="clipboard-list" title="No attempts yet" text="Pick your primary skill above and go for it. You’ve got this 💪" />
@else
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Skill</th><th>Date</th><th>Score</th><th>Result</th><th></th></tr></thead>
        <tbody>
        @foreach($attempts as $at)
            <tr><td class="strong">{{ $at->assessment->skill->name }}</td><td>{{ $at->created_at->format('j M Y') }}</td>
                <td>{{ $at->status === 'in_progress' ? '—' : $at->percentage.'%' }}</td>
                <td><x-status :status="$at->status" :label="$at->statusLabel()" /> @if($at->level_awarded)<x-level :level="$at->level_awarded" />@endif</td>
                <td class="actions">@if($at->status === 'in_progress')<a class="btn btn-sm" href="{{ route('app.attempts.take', $at) }}">Resume</a>@else<a class="btn btn-sm" href="{{ route('app.attempts.result', $at) }}">Details</a>@endif</td></tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $attempts->links() }}
@endif
@endsection
