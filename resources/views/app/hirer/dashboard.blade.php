@extends('layouts.app')
@section('title', 'Hiring dashboard')
@section('content')
<div class="page-head">
    <div><h1>Let’s find your people, {{ auth()->user()->firstName() }} 🔎</h1>
        <p>@if($org){{ $org->name }} · @if($subscription){{ $subscription->plan->name }} plan @else Pay-as-you-go @endif @else Set up your company to unlock contacts and post jobs. @endif</p></div>
    <div class="row"><a href="{{ route('app.projects.create') }}" class="btn"><i data-lucide="rocket"></i> Post a project</a>@if($org)<a href="{{ route('app.jobs.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Post a job</a>@endif</div>
</div>

@if(! $org)
    <div class="card card-pop mb-3 row"><div class="icon-tile lime"><i data-lucide="building-2"></i></div><div class="grow"><h3 class="mb-0">Set up your company</h3><p class="muted mb-0 small">Needed for job posts, shortlists and contact unlocks. Projects work without it.</p></div><a class="btn btn-primary" href="{{ route('app.company.edit') }}">Set up</a></div>
@endif

<form method="POST" action="{{ route('talent.ai') }}" class="ai-panel mb-3">
    @csrf
    <div class="row mb-1"><span class="sparkle"><i data-lucide="sparkles"></i></span><strong>Describe who you need — AI turns it into filters</strong></div>
    <div class="row" style="flex-wrap:nowrap"><input class="input input-lg grow" name="prompt" placeholder="Senior Laravel dev, Lagos or remote, verified, open to contract" required maxlength="400"><button class="btn ai-btn btn-lg"><i data-lucide="wand-sparkles"></i><span class="hide-mobile">Find</span></button></div>
</form>

<div class="kpis mb-3">
    <div class="kpi"><div class="label"><i data-lucide="unlock"></i> Unlocks available</div><div class="value">{{ $stats['unlocks'] }}</div><div class="sub"><a class="link" href="{{ route('app.billing') }}">Top up</a></div></div>
    <div class="kpi"><div class="label"><i data-lucide="list-checks"></i> Shortlisted</div><div class="value">{{ $stats['shortlisted'] }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="users"></i> Applicants</div><div class="value">{{ $stats['applicants'] }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="file-text"></i> New proposals</div><div class="value">{{ $stats['proposals'] }}</div></div>
</div>

<div class="split">
    <div class="stack" style="--stack:20px">
        <div class="card">
            <div class="card-head"><h3>Your jobs</h3>@if($org)<a href="{{ route('app.jobs.index') }}" class="link small">Manage</a>@endif</div>
            <div class="list-divided">
                @forelse($jobs as $job)
                    <a href="{{ route('app.jobs.pipeline', $job) }}" class="row-between"><span><strong>{{ $job->title }}</strong><span class="small muted" style="display:block">{{ $job->applications_count }} applicants · {{ $job->views }} views</span></span><span class="row">@if($job->new_count)<span class="badge badge-violet">{{ $job->new_count }} new</span>@endif<x-status :status="$job->status" /></span></a>
                @empty
                    <p class="muted mb-0">No jobs yet. Post one and verified talent can apply in a tap.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h3>Your projects</h3><a href="{{ route('app.projects.index') }}" class="link small">Manage</a></div>
            <div class="list-divided">
                @forelse($projects as $p)
                    <a href="{{ route('app.projects.proposals', $p) }}" class="row-between"><span><strong>{{ $p->title }}</strong><span class="small muted" style="display:block">{{ $p->proposals_count }} proposals · {{ $p->budgetLabel() }}</span></span><x-status :status="$p->status" /></a>
                @empty
                    <p class="muted mb-0">Need something built? Post a project and pay by milestone.</p>
                @endforelse
            </div>
        </div>
        @if($contracts->isNotEmpty())
            <div class="card">
                <div class="card-head"><h3>Active contracts</h3><a href="{{ route('app.contracts.index') }}" class="link small">All</a></div>
                <div class="list-divided">
                    @foreach($contracts as $c)
                        @php($needsAction = $c->milestones->first(fn ($m) => in_array($m->status, ['submitted', 'pending'])))
                        <a href="{{ route('app.contracts.show', $c) }}" class="row-between"><span><strong>{{ $c->title }}</strong><span class="small muted" style="display:block">with {{ $c->professional->name }}</span></span>
                            @if($needsAction?->status === 'submitted')<span class="badge badge-amber">Review work</span>@elseif($needsAction?->status === 'pending' && $c->status === 'active')<span class="badge badge-violet">Fund next</span>@else<x-status :status="$c->status" :label="$c->statusLabel()" />@endif</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    <aside class="stack" style="--stack:16px">
        <div class="card"><h3>Recently verified</h3>
            @foreach($recent as $p)
                <a href="{{ route('talent.show', $p) }}" class="row mt-2"><img class="avatar" src="{{ $p->user->avatarUrl() }}" alt=""><span class="grow"><strong class="small">{{ $p->user->name }}</strong><span class="small muted" style="display:block">{{ $p->primary_role }}</span></span>@if($p->topVerifiedLevel())<x-level :level="$p->topVerifiedLevel()" />@endif</a>
            @endforeach
            <a href="{{ route('talent.index') }}" class="btn btn-block btn-sm mt-2">Search all talent</a>
        </div>
        @if($subscription)
            <div class="card card-soft"><h4>{{ $subscription->plan->name }} plan</h4><p class="small mb-1">{{ $subscription->unlocks_remaining }} of {{ $subscription->plan->contact_unlocks }} unlocks left this period.</p><div class="meter"><i style="width:{{ $subscription->plan->contact_unlocks ? round($subscription->unlocks_remaining / $subscription->plan->contact_unlocks * 100) : 0 }}%"></i></div><p class="small muted mt-1 mb-0">Renews {{ $subscription->current_period_end->format('j M Y') }}</p></div>
        @endif
    </aside>
</div>
@endsection
