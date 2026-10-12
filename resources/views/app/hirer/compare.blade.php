@extends('layouts.app')
@section('title', 'Compare')
@section('content')
<x-page-head title="Compare candidates" subtitle="Side-by-side on evidence: verified levels, experience and delivery." />
@if($profiles->count() < 2)
    <x-empty icon="columns-3" title="Pick at least two people" text="Open a shortlist and hit Compare." />
@else
<div class="table-wrap"><table class="table">
    <thead><tr><th></th>@foreach($profiles as $p)<th style="text-transform:none;letter-spacing:0;font-size:.9rem"><a href="{{ route('talent.show', $p) }}" class="row"><img class="avatar avatar-sm" src="{{ $p->user->avatarUrl() }}" alt="">{{ $p->user->name }}</a></th>@endforeach</tr></thead>
    <tbody>
        <tr><td class="strong">Role</td>@foreach($profiles as $p)<td>{{ $p->primary_role }}</td>@endforeach</tr>
        <tr><td class="strong">Experience</td>@foreach($profiles as $p)<td>{{ $p->years_experience }} yrs</td>@endforeach</tr>
        <tr><td class="strong">Location</td>@foreach($profiles as $p)<td>{{ $p->location() }}</td>@endforeach</tr>
        <tr><td class="strong">Availability</td>@foreach($profiles as $p)<td>{{ $p->availabilityLabel() }}</td>@endforeach</tr>
        <tr><td class="strong">Rate</td>@foreach($profiles as $p)<td class="money">{{ $p->show_rate ? $p->rateLabel() : 'Hidden' }}</td>@endforeach</tr>
        <tr><td class="strong">Completed contracts</td>@foreach($profiles as $p)<td>{{ $p->stats()['completed'] }}</td>@endforeach</tr>
        <tr><td class="strong">Client rating</td>@foreach($profiles as $p)<td>{{ $p->stats()['reviews'] ? '★ '.$p->stats()['rating'] : '—' }}</td>@endforeach</tr>
        @foreach($skills as $skill)
            <tr><td>{{ $skill->name }}</td>@foreach($profiles as $p)@php($ps = $p->skills->firstWhere('id', $skill->id))<td>@if($ps?->pivot->verified_level)<x-level :level="$ps->pivot->verified_level" />@elseif($ps)<span class="badge">Self-reported</span>@else<span class="muted">—</span>@endif</td>@endforeach</tr>
        @endforeach
    </tbody>
</table></div>
@endif
@endsection
