@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-head">
    <div>
        <h1>Hey {{ auth()->user()->firstName() }} ✌️</h1>
        <p>Profile status: <x-status :status="$profile->status" :label="$profile->statusLabel()" /> @if($profile->isDiscoverable()) · <a class="link" href="{{ route('talent.show', $profile) }}">You’re live — view public profile</a>@endif</p>
    </div>
    <div class="row"><a href="{{ route('jobs.index') }}" class="btn"><i data-lucide="briefcase"></i> Browse jobs</a><a href="{{ route('app.assessments.index') }}" class="btn btn-accent"><i data-lucide="badge-check"></i> Get verified</a></div>
</div>

@if($profile->review_notes && in_array($profile->status, ['draft', 'under_review', 'suspended']))
    <div class="alert alert-warn mb-3"><i data-lucide="message-square-warning"></i><div><strong>Note from the review team:</strong> {{ $profile->review_notes }}</div></div>
@endif

<div class="kpis mb-3">
    <div class="kpi"><div class="label"><i data-lucide="eye"></i> Profile views</div><div class="value">{{ number_format($stats['views']) }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="send"></i> Active applications</div><div class="value">{{ $stats['applications'] }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="file-text"></i> Open proposals</div><div class="value">{{ $stats['proposals'] }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="wallet"></i> Earnings balance</div><div class="value money">{{ money($stats['earnings']) }}</div></div>
</div>

<div class="split">
    <div class="stack" style="--stack:20px">
        @if($completion < 100 || in_array($profile->status, ['draft', 'awaiting_assessment']))
            <div class="card card-pop">
                <div class="row" style="gap:20px">
                    <div class="progress-ring" style="--p: {{ $completion }}" data-label="{{ $completion }}%"></div>
                    <div class="grow"><h3 class="mb-0">Level up your profile</h3><p class="muted mb-0">Complete these to go live and show up in employer searches.</p></div>
                </div>
                <ul class="checklist mt-2">
                    @foreach($checklist as $item)
                        <li @class(['done' => $item['done']])><a href="{{ route($item['route']) }}"><span class="tick">@if($item['done'])<i data-lucide="check"></i>@endif</span><span class="grow">{{ $item['label'] }}</span><i data-lucide="chevron-right" style="width:18px"></i></a></li>
                    @endforeach
                </ul>
                @if(in_array($profile->status, ['draft', 'awaiting_assessment']))
                    <form method="POST" action="{{ route('app.profile.submit') }}" class="mt-2">@csrf<button class="btn btn-primary btn-block" type="submit"><i data-lucide="rocket"></i> {{ $profile->status === 'draft' ? 'Submit profile for review' : 'Re-check my status' }}</button></form>
                @endif
            </div>
        @endif

        @if($contracts->isNotEmpty())
            <div class="card">
                <div class="card-head"><h3>Active contracts</h3><a class="link small" href="{{ route('app.contracts.index') }}">All</a></div>
                <div class="list-divided">
                    @foreach($contracts as $c)
                        @php($next = $c->milestones->first(fn ($m) => ! in_array($m->status, ['released', 'refunded'])))
                        <a href="{{ route('app.contracts.show', $c) }}" class="row-between"><span><strong>{{ $c->title }}</strong><span class="small muted" style="display:block">{{ $next ? 'Next: '.$next->title.' · '.$next->statusLabel() : 'All milestones settled' }}</span></span><x-status :status="$c->status" :label="$c->statusLabel()" /></a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-head"><h3>Jobs matching your skills</h3><a class="link small" href="{{ route('jobs.index') }}">Browse all</a></div>
            <div class="list-divided">
                @forelse($jobs as $job)
                    <a href="{{ route('jobs.show', $job) }}" class="row"><span class="logo-tile">{{ mb_substr($job->organization->name, 0, 1) }}</span><span class="grow"><strong>{{ $job->title }}</strong><span class="small muted" style="display:block">{{ $job->organization->name }} · {{ $job->typeLabel() }}</span></span>@if($job->salaryLabel())<span class="small strong money">{{ $job->salaryLabel() }}</span>@endif</a>
                @empty
                    <p class="muted mb-0">Claim more skills to get better matches.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h3>Projects you could pitch for</h3><a class="link small" href="{{ route('projects.index') }}">Browse all</a></div>
            <div class="list-divided">
                @forelse($projects as $p)
                    <a href="{{ route('projects.show', $p) }}" class="row-between"><strong>{{ $p->title }}</strong><span class="small strong money">{{ $p->budgetLabel() }}</span></a>
                @empty
                    <p class="muted mb-0">No matching projects right now.</p>
                @endforelse
            </div>
        </div>
    </div>

    <aside class="stack" style="--stack:20px">
        <div class="card">
            <h3>Next badge to earn</h3>
            @forelse($suggested as $a)
                <a href="{{ route('app.assessments.show', $a) }}" class="row mt-2" style="padding:12px;border:1px solid var(--line);border-radius:14px"><span class="icon-tile sm lime"><i data-lucide="badge-check"></i></span><span class="grow"><strong>{{ $a->skill->name }}</strong><span class="small muted" style="display:block">{{ $a->duration_minutes }} min · {{ $a->pass_mark }}% to pass</span></span><i data-lucide="arrow-right" style="width:18px"></i></a>
            @empty
                <p class="muted small">You’ve verified every claimed skill that has an assessment. Legend 🏆</p>
            @endforelse
        </div>
        <div class="card">
            <h3>Recent attempts</h3>
            @forelse($attempts as $at)
                <div class="row-between mt-1"><span class="small">{{ $at->assessment->skill->name }}</span><x-status :status="$at->status" :label="$at->statusLabel()" /></div>
            @empty
                <p class="muted small mb-0">No attempts yet.</p>
            @endforelse
        </div>
        @if($stats['requests'])
            <a href="{{ route('app.requests.index') }}" class="card card-lime row"><i data-lucide="inbox"></i><strong class="grow">{{ $stats['requests'] }} new contact {{ \Illuminate\Support\Str::plural('request', $stats['requests']) }}</strong><i data-lucide="arrow-right"></i></a>
        @endif
        <div class="card card-ink">
            <span class="sparkle"><i data-lucide="sparkles"></i></span>
            <h3 class="mt-2">Bio glow-up</h3>
            <p class="muted small">Let AI turn your skills and projects into a headline + bio that slaps. You review before saving.</p>
            <a href="{{ route('app.profile.edit') }}#ai" class="btn btn-accent btn-sm">Try it</a>
        </div>
    </aside>
</div>
@endsection
