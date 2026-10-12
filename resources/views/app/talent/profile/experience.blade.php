@extends('layouts.app')
@section('title', 'Experience')
@section('content')
<x-page-head title="Experience & credentials" subtitle="Work history, education and certifications. Be specific — employers read this." />
@include('app.talent.profile._nav')
<div class="split">
    <div class="stack" style="--stack:20px">
        <div class="card">
            <h3>Timeline</h3>
            @if($profile->experiences->isEmpty())<p class="muted">Nothing here yet. Add your current or most recent role.</p>@endif
            <ul class="timeline mt-2">
                @foreach($profile->experiences as $exp)
                    <li x-data="{ edit: false }">
                        <span class="node {{ $exp->is_current ? 'violet' : '' }}"><i data-lucide="{{ $exp->kind === 'education' ? 'graduation-cap' : 'briefcase' }}"></i></span>
                        <div class="row-between"><strong>{{ $exp->title }} · {{ $exp->organization }}</strong>
                            <span class="row gap-xs"><button class="btn btn-ghost btn-sm btn-icon" @click="edit = !edit" aria-label="Edit"><i data-lucide="pencil"></i></button>
                            <form method="POST" action="{{ route('app.profile.experience.delete', $exp) }}" data-confirm="Delete this entry?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm btn-icon" aria-label="Delete"><i data-lucide="trash-2"></i></button></form></span></div>
                        <div class="small muted">{{ $exp->started_on?->format('M Y') }} – {{ $exp->is_current ? 'Present' : $exp->ended_on?->format('M Y') }}</div>
                        @if($exp->description)<p class="small mt-1">{{ $exp->description }}</p>@endif
                        <div x-show="edit" x-cloak class="card card-soft mt-2">@include('app.talent.profile._experience-form', ['exp' => $exp])</div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card">
            <h3>Certifications</h3>
            <div class="list-divided">
                @forelse($profile->certifications as $c)
                    <div class="row-between"><div><strong>{{ $c->name }}</strong><div class="small muted">{{ $c->issuer }} @if($c->credential_url)· <a class="link" href="{{ $c->credential_url }}" target="_blank" rel="noopener">credential</a>@endif</div></div>
                        <div class="row">@if($c->verified_at)<span class="badge badge-green">Verified</span>@else<span class="badge">Pending check</span>@endif
                            <form method="POST" action="{{ route('app.profile.certifications.delete', $c) }}" data-confirm="Remove?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm btn-icon" aria-label="Remove"><i data-lucide="trash-2"></i></button></form></div></div>
                @empty
                    <p class="muted mb-0">Optional, but nice: AWS, Azure, CompTIA, Google, Scrum…</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('app.profile.certifications.save') }}" class="form-grid mt-2">
                @csrf
                <div class="field"><label for="c_name">Certification</label><input id="c_name" class="input" name="name" required></div>
                <div class="field"><label for="c_issuer">Issuer</label><input id="c_issuer" class="input" name="issuer" required></div>
                <div class="field"><label for="c_issued">Issued</label><input id="c_issued" type="date" class="input" name="issued_on"></div>
                <div class="field"><label for="c_url">Credential URL</label><input id="c_url" type="url" class="input" name="credential_url"></div>
                <div class="span-2"><button class="btn btn-sm">Add certification</button></div>
            </form>
        </div>
    </div>
    <aside class="sticky"><div class="card card-pop"><h3>Add an entry</h3>@include('app.talent.profile._experience-form', ['exp' => null])</div></aside>
</div>
@endsection
