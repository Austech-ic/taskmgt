@extends('layouts.admin')
@section('title', $profile->user->name)
@section('content')
<a href="{{ route('admin.talent.index') }}" class="small link">← Approvals</a>
<x-page-head :title="$profile->user->name" :subtitle="$profile->primary_role.' · '.$profile->location().' · joined '.$profile->user->created_at->format('M Y')">
    <x-status :status="$profile->status" :label="$profile->statusLabel()" /><a class="btn btn-sm" href="{{ route('talent.show', $profile) }}" target="_blank"><i data-lucide="external-link"></i> Public view</a>
</x-page-head>
<div class="split">
    <div class="stack" style="--stack:20px">
        <div class="card"><h3>{{ $profile->headline ?: 'No headline' }}</h3><p class="small" style="white-space:pre-wrap">{{ $profile->bio ?: 'No bio.' }}</p>
            <div class="small muted">{{ $profile->years_experience }} yrs · {{ $profile->availabilityLabel() }} · {{ $profile->user->email }} · {{ $profile->contact_phone }}</div>
            <div class="row small mt-1">@foreach(['github_url', 'gitlab_url', 'linkedin_url', 'portfolio_url', 'behance_url'] as $f)@if($profile->$f)<a class="link" href="{{ $profile->$f }}" target="_blank" rel="noopener nofollow">{{ str_replace('_url', '', $f) }} ↗</a>@endif @endforeach</div></div>
        <div class="card"><h3>Skills</h3>
            @foreach($profile->profileSkills as $ps)<div class="row-between mt-1"><span>{{ $ps->skill->name }} @if($ps->is_primary)<span class="badge badge-ink">primary</span>@endif</span>@if($ps->verified_level)<x-level :level="$ps->verified_level" />@else<span class="badge">self L{{ $ps->self_level }}</span>@endif</div>@endforeach</div>
        <div class="card"><h3>Assessment history</h3>
            @forelse($attempts as $a)<div class="row-between mt-1 small"><a class="link" href="{{ route('admin.attempts.show', $a) }}">{{ $a->assessment->skill->name }} · {{ $a->created_at->format('j M Y') }}</a><span>{{ $a->percentage }}% <x-status :status="$a->status" :label="$a->statusLabel()" /></span></div>@empty<p class="muted small mb-0">No attempts.</p>@endforelse</div>
        <div class="card"><h3>Portfolio</h3>
            @forelse($profile->portfolio as $item)
                <div class="row-between mt-1"><span><strong>{{ $item->title }}</strong> <span class="small muted">{{ $item->role }}</span> @if($item->url)<a class="link small" href="{{ $item->url }}" target="_blank" rel="noopener nofollow">↗</a>@endif</span>
                    <form method="POST" action="{{ route('admin.talent.verify', $profile) }}">@csrf<input type="hidden" name="what" value="portfolio_item"><input type="hidden" name="id" value="{{ $item->id }}">@if($item->verified_at)<input type="hidden" name="undo" value="1"><button class="btn btn-sm">Unverify</button>@else<button class="btn btn-sm">Mark verified</button>@endif</form></div>
            @empty<p class="muted small mb-0">No projects.</p>@endforelse</div>
        <div class="card"><h3>Experience & certifications</h3>
            @foreach($profile->experiences as $e)<div class="small mt-1"><strong>{{ $e->title }}</strong> · {{ $e->organization }} ({{ $e->started_on?->format('Y') }}–{{ $e->is_current ? 'now' : $e->ended_on?->format('Y') }})</div>@endforeach
            @foreach($profile->certifications as $c)
                <div class="row-between mt-1 small"><span>🎓 {{ $c->name }} · {{ $c->issuer }} @if($c->credential_url)<a class="link" href="{{ $c->credential_url }}" target="_blank" rel="noopener nofollow">check ↗</a>@endif</span>
                    <form method="POST" action="{{ route('admin.talent.verify', $profile) }}">@csrf<input type="hidden" name="what" value="certification"><input type="hidden" name="id" value="{{ $c->id }}">@if($c->verified_at)<input type="hidden" name="undo" value="1"><button class="btn btn-sm">Unverify</button>@else<button class="btn btn-sm">Verify credential</button>@endif</form></div>
            @endforeach</div>
        <div class="card card-soft"><h3>History</h3>@foreach($audit as $l)<div class="small mt-1"><span class="mono">{{ $l->action }}</span> · {{ $l->user?->name ?? 'system' }} · {{ $l->created_at->diffForHumans() }}</div>@endforeach</div>
    </div>
    <aside class="sticky stack" style="--stack:16px">
        <form method="POST" action="{{ route('admin.talent.decide', $profile) }}" class="card card-pop">
            @csrf
            <h3>Decision</h3>
            <div class="field"><select class="select" name="decision" required>
                <option value="approve">✅ Approve (fully)</option><option value="conditional">🟡 Approve conditionally</option><option value="changes">✏️ Request changes</option>
                @if($profile->status === 'suspended')<option value="reinstate">↩️ Reinstate</option>@else<option value="suspend">⛔ Suspend</option>@endif
            </select></div>
            <div class="field"><label>Note to the professional</label><textarea class="textarea" name="notes" rows="4" placeholder="Required for changes/suspension">{{ $profile->review_notes }}</textarea></div>
            <button class="btn btn-primary btn-block">Save decision</button>
        </form>
        <div class="card">
            <h4>Identity & portfolio checks</h4>
            <p class="small muted">Identity verification ≠ skill verification. Record checks done outside the platform (ID document, video call, repo ownership).</p>
            @foreach(['identity' => 'identity_verified_at', 'portfolio' => 'portfolio_verified_at'] as $what => $col)
                <form method="POST" action="{{ route('admin.talent.verify', $profile) }}" class="row-between mt-1">@csrf<input type="hidden" name="what" value="{{ $what }}"><span class="small">{{ ucfirst($what) }}: <strong>{{ $profile->$col ? 'verified '.$profile->$col->format('j M Y') : 'not verified' }}</strong></span>@if($profile->$col)<input type="hidden" name="undo" value="1"><button class="btn btn-sm">Undo</button>@else<button class="btn btn-sm btn-primary">Verify</button>@endif</form>
            @endforeach
        </div>
    </aside>
</div>
@endsection
