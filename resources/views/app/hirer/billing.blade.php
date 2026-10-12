@extends('layouts.app')
@section('title', 'Billing')
@section('content')
<x-page-head title="Billing & plans" subtitle="Subscriptions include monthly contact unlocks and team seats. Credits never expire." />
@if(setting('payment_provider') === 'sandbox')
    <div class="alert alert-warn mb-3"><i data-lucide="flask-conical"></i><div><strong>Test mode:</strong> payments use the sandbox checkout — no real money moves.</div></div>
@endif
<div class="kpis mb-3" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <div class="kpi"><div class="label">Current plan</div><div class="value" style="font-size:1.4rem">{{ $subscription?->plan->name ?? 'Pay as you go' }}</div>@if($subscription)<div class="sub">Renews {{ $subscription->current_period_end->format('j M Y') }}</div>@endif</div>
    <div class="kpi"><div class="label">Plan unlocks left</div><div class="value">{{ $subscription?->unlocks_remaining ?? 0 }}</div></div>
    <div class="kpi"><div class="label">Credits</div><div class="value">{{ $org->unlock_credits }}</div></div>
</div>
<div class="grid-3 mb-4">
    @foreach($plans as $plan)
        <div class="card price-card {{ $plan->is_featured ? 'featured' : '' }}">
            <div class="row-between"><h3 class="mb-0">{{ $plan->name }}</h3>@if($subscription?->plan_id === $plan->id)<span class="badge badge-green">Current</span>@endif</div>
            <div class="price money">{{ money($plan->price_minor, $plan->currency) }}<span class="small muted" style="font-family:var(--font);font-weight:500">/{{ $plan->interval }}</span></div>
            <ul>@foreach($plan->features ?? [] as $f)<li><i data-lucide="check"></i>{{ $f }}</li>@endforeach</ul>
            <form method="POST" action="{{ route('app.billing.subscribe', $plan) }}" style="margin-top:auto">@csrf<button class="btn {{ $plan->is_featured ? 'btn-accent' : 'btn-primary' }} btn-block">{{ $subscription?->plan_id === $plan->id ? 'Renew' : 'Choose '.$plan->name }}</button></form>
        </div>
    @endforeach
    <div class="card card-ink price-card">
        <h3 class="mb-0">Contact credits</h3>
        <p class="muted small mb-0">{{ money($unitPrice) }} per unlock. Use any time.</p>
        <form method="POST" action="{{ route('app.billing.credits') }}" class="stack" style="--stack:10px;margin-top:auto">
            @csrf
            @foreach($bundles as $b)
                <label class="check" style="padding:10px 12px;border:1px solid rgba(255,255,255,.2);border-radius:12px"><input type="radio" name="quantity" value="{{ $b }}" @checked($b === 5)> <span class="grow">{{ $b }} {{ \Illuminate\Support\Str::plural('unlock', $b) }}</span><strong class="money">{{ money($b * $unitPrice) }}</strong></label>
            @endforeach
            <button class="btn btn-accent btn-block">Buy credits</button>
        </form>
    </div>
</div>
<h2 style="font-size:1.3rem">Payment history</h2>
@if($transactions->isEmpty())
    <p class="muted">No payments yet.</p>
@else
    <div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Reference</th><th>For</th><th class="right">Amount</th><th>Status</th></tr></thead><tbody>
        @foreach($transactions as $t)<tr><td class="nowrap">{{ $t->created_at->format('j M Y') }}</td><td class="mono small">{{ $t->reference }}</td><td>{{ $t->purposeLabel() }}</td><td class="right money">{{ money($t->amount_minor, $t->currency) }}</td><td><x-status :status="$t->status" /></td></tr>@endforeach
    </tbody></table></div>
@endif
@endsection
