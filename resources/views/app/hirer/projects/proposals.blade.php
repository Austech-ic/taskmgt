@extends('layouts.app')
@section('title', 'Proposals · '.$project->title)
@section('content')
<a href="{{ route('app.projects.index') }}" class="small link">← Projects</a>
<x-page-head :title="$project->title" :subtitle="$proposals->count().' proposals · '.$project->budgetLabel()"><a class="btn" href="{{ route('projects.show', $project) }}" target="_blank"><i data-lucide="external-link"></i> Public page</a></x-page-head>
@forelse($proposals as $prop)
    @php($p = $prop->profile)
    <div class="card mb-3 {{ $prop->status === 'shortlisted' ? 'card-pop' : '' }}">
        <div class="row-between row-top">
            <a class="row" href="{{ route('talent.show', $p) }}" target="_blank"><img class="avatar avatar-lg" src="{{ $p->user->avatarUrl() }}" alt=""><div><strong>{{ $p->user->name }}</strong> @if($p->topVerifiedLevel())<x-level :level="$p->topVerifiedLevel()" />@endif<div class="small muted">{{ $p->primary_role }} · {{ $p->years_experience }} yrs · {{ $p->location() }}</div>
                @php($st = $p->stats())<div class="small muted">{{ $st['completed'] }} completed · {{ $st['reviews'] ? '★ '.$st['rating'] : 'no reviews yet' }}</div></div></a>
            <div class="right"><div style="font:800 1.5rem var(--display)" class="money">{{ money($prop->amount_minor, $project->currency) }}</div><div class="small muted">{{ $prop->duration_days }} days · <x-status :status="$prop->status" /></div></div>
        </div>
        <div class="chips mt-2">@foreach($p->verifiedSkills()->take(5) as $s)<span class="chip chip-verified">{{ $s->name }} L{{ $s->pivot->verified_level }}</span>@endforeach</div>
        <p class="mt-2" style="white-space:pre-wrap">{{ $prop->cover_letter }}</p>
        @if($prop->approach)<details class="mb-2"><summary class="strong small" style="cursor:pointer">Approach & assumptions</summary><p class="small mt-1" style="white-space:pre-wrap">{{ $prop->approach }}</p></details>@endif
        @if($prop->milestones)
            <div class="card card-soft small"><strong>Proposed milestones</strong>@foreach($prop->milestones as $m)<div class="row-between mt-1"><span>{{ $m['title'] }} @if(! empty($m['days']))<span class="muted">· {{ $m['days'] }}d</span>@endif</span><span class="money">{{ money($m['amount_minor'], $project->currency) }}</span></div>@endforeach</div>
        @endif
        @if(in_array($prop->status, ['submitted', 'shortlisted']) && $project->status === 'open')
            <div class="row mt-2">
                <a class="btn btn-accent" href="{{ route('app.projects.hire', [$project, $prop]) }}"><i data-lucide="handshake"></i> Hire {{ $p->user->firstName() }}</a>
                @if($prop->status === 'submitted')<form method="POST" action="{{ route('app.projects.proposals.shortlist', [$project, $prop]) }}">@csrf<button class="btn"><i data-lucide="star"></i> Shortlist</button></form>@endif
                <form method="POST" action="{{ route('app.projects.proposals.shortlist', [$project, $prop]) }}">@csrf<input type="hidden" name="action" value="decline"><button class="btn btn-ghost">Decline</button></form>
            </div>
        @endif
    </div>
@empty
    <x-empty icon="inbox" title="No proposals yet" text="Verified pros get notified about projects matching their skills. Hang tight ✨" />
@endforelse
@endsection
