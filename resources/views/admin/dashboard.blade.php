@extends('layouts.admin')
@section('title', 'Admin')
@section('content')
<x-page-head title="Command centre" :subtitle="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening')).', '.auth()->user()->firstName().'. Here’s what needs you.'" />
@if(setting('payment_provider') === 'sandbox')
    <div class="alert alert-warn mb-3"><i data-lucide="flask-conical"></i><div><strong>Payments are in TEST MODE.</strong> No real money moves. Connect Paystack in <a class="link" href="{{ route('admin.settings', 'payments') }}">Settings → Payments</a> before launch — and get legal advice on holding milestone funds.</div></div>
@endif
@unless(setting('ai_enabled'))
    <div class="alert alert-info mb-3"><i data-lucide="sparkles"></i><div>AI features are off. Add an Anthropic API key in <a class="link" href="{{ route('admin.settings', 'ai') }}">Settings → AI</a> to unlock bio glow-ups, smart search, job & project writers and the Hail assistant.</div></div>
@endunless
@unless($ledgerBalanced)
    <div class="alert alert-error mb-3"><i data-lucide="alert-octagon"></i><div><strong>Ledger out of balance.</strong> Investigate in Finance → Ledger immediately.</div></div>
@endunless

<div class="grid-auto mb-3" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
    @foreach($queues as [$label, $count, $route, $icon, $roles])
        @if(auth()->user()->hasStaffRole(...$roles))
            <a href="{{ route($route) }}" class="card card-hover row {{ $count ? 'card-pop' : '' }}" style="flex-wrap:nowrap"><span class="icon-tile sm {{ $count ? 'lime' : '' }}"><i data-lucide="{{ $icon }}"></i></span><span class="grow small strong">{{ $label }}</span><span style="font:800 1.4rem var(--display)">{{ $count }}</span></a>
        @endif
    @endforeach
</div>

<div class="kpis mb-3">
    <div class="kpi"><div class="label"><i data-lucide="user-check"></i> Approved pros</div><div class="value">{{ number_format($kpis['approved']) }}</div><div class="sub">{{ $kpis['with_verified'] !== null ? $kpis['with_verified'].'% have a verified skill' : '—' }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="users"></i> Users</div><div class="value">{{ number_format($kpis['users']) }}</div><div class="sub">{{ $kpis['orgs'] }} companies</div></div>
    @if(auth()->user()->hasStaffRole('finance', 'admin'))<div class="kpi"><div class="label"><i data-lucide="arrow-left-right"></i> Milestone volume</div><div class="value money">{{ money($kpis['gmv'], 'NGN', false) }}</div><div class="sub">{{ $kpis['contracts'] }} active contracts</div></div>
    <div class="kpi"><div class="label"><i data-lucide="landmark"></i> Revenue (ledger)</div><div class="value money">{{ money($kpis['revenue'], 'NGN', false) }}</div><div class="sub">commission + unlocks + plans</div></div>@endif
</div>

<div class="split">
    <div class="stack" style="--stack:20px">
        <div class="card">
            <div class="card-head"><h3>Sign-ups · last 14 days</h3><span class="small muted">{{ $chart->sum('count') }} total</span></div>
            @php($max = max(1, $chart->max('count')))
            <div style="display:flex;align-items:flex-end;gap:6px;height:160px" role="img" aria-label="Daily sign-ups chart">
                @foreach($chart as $d)
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end" title="{{ $d['date'] }}: {{ $d['count'] }}">
                        <span class="small muted" style="font-size:.7rem">{{ $d['count'] ?: '' }}</span>
                        <div style="width:100%;height:{{ max(2, round($d['count'] / $max * 120)) }}px;background:{{ $loop->last ? 'var(--accent)' : 'var(--primary)' }};border-radius:6px 6px 2px 2px;{{ $loop->last ? 'border:1.5px solid var(--ink)' : '' }}"></div>
                        <span class="small muted" style="font-size:.66rem">{{ \Illuminate\Support\Carbon::parse($d['date'])->format('d') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h3>Recent activity</h3>@if(auth()->user()->hasStaffRole('admin'))<a class="link small" href="{{ route('admin.audit') }}">Audit log</a>@endif</div>
            <div class="list-divided small">
                @forelse($recent as $log)
                    <div class="row-between"><span><span class="mono">{{ $log->action }}</span> · {{ $log->user?->name ?? 'system' }}</span><span class="muted">{{ $log->created_at->diffForHumans() }}</span></div>
                @empty
                    <p class="muted mb-0">Nothing yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <aside class="stack" style="--stack:16px">
        <div class="card"><h4>Assessment pass rate</h4><div class="stat-big">{{ $kpis['pass_rate'] !== null ? $kpis['pass_rate'].'%' : '—' }}</div><p class="small muted mb-0">Healthy is usually 40–70%. Much higher may mean questions are leaking or too easy.</p></div>
        <div class="card"><h4>Live listings</h4><p class="mb-0">{{ $kpis['jobs'] }} jobs · {{ $kpis['projects'] }} projects</p></div>
        <div class="card card-soft"><h4>AI usage (30 days)</h4><p class="mb-0">{{ number_format($aiCalls) }} requests</p></div>
    </aside>
</div>
@endsection
