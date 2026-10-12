@extends('layouts.app')
@section('title', 'Earnings')
@section('content')
<x-page-head title="Earnings" subtitle="Released milestone payments, net of commission. Every movement is recorded in a double-entry ledger." />
<div class="kpis mb-3" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <div class="kpi" style="background:var(--ink);color:var(--paper)"><div class="label" style="color:inherit;opacity:.7"><i data-lucide="wallet"></i> Available to withdraw</div><div class="value money">{{ money($available) }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="hourglass"></i> Payouts processing</div><div class="value money">{{ money($pending) }}</div></div>
    <div class="kpi"><div class="label"><i data-lucide="piggy-bank"></i> Ledger balance</div><div class="value money">{{ money($balance) }}</div></div>
</div>
<div class="split">
    <div class="stack" style="--stack:20px">
        <div class="card card-flush">
            <div style="padding:18px 20px 0"><h3>Activity</h3></div>
            @if($entries->isEmpty())
                <div style="padding:20px"><x-empty icon="banknote" title="No earnings yet" text="When a client approves a milestone, the payout lands here." /></div>
            @else
                <table class="table"><thead><tr><th>Date</th><th>Description</th><th class="right">Amount</th></tr></thead><tbody>
                    @foreach($entries as $e)
                        <tr><td class="nowrap">{{ $e->created_at->format('j M Y') }}</td><td>{{ $e->memo }}</td><td class="right money strong" style="color:{{ $e->credit_minor ? 'var(--green)' : 'var(--ink)' }}">{{ $e->credit_minor ? '+'.money($e->credit_minor) : '−'.money($e->debit_minor) }}</td></tr>
                    @endforeach
                </tbody></table>
                <div style="padding:0 16px 16px">{{ $entries->links() }}</div>
            @endif
        </div>
        @if($payouts->isNotEmpty())
            <div class="card"><h3>Payout requests</h3>
                @foreach($payouts as $p)<div class="row-between mt-1"><span class="small">{{ $p->created_at->format('j M Y') }} · {{ $p->bank_name }} ••{{ substr($p->account_number, -4) }}</span><span class="row"><strong class="money">{{ money($p->amount_minor) }}</strong><x-status :status="$p->status" /></span></div>@endforeach
            </div>
        @endif
    </div>
    <aside class="sticky">
        <form method="POST" action="{{ route('app.earnings.payout') }}" class="card card-pop">
            @csrf
            <h3>Withdraw 💸</h3>
            <div class="field"><label for="amount">Amount (₦)</label><input id="amount" class="input" name="amount" inputmode="decimal" value="{{ to_major($available) }}" required></div>
            <div class="field"><label for="bank_name">Bank</label><input id="bank_name" class="input" name="bank_name" placeholder="e.g. GTBank" required></div>
            <div class="field"><label for="account_number">Account number</label><input id="account_number" class="input" name="account_number" inputmode="numeric" maxlength="12" required></div>
            <div class="field"><label for="account_name">Account name</label><input id="account_name" class="input" name="account_name" value="{{ auth()->user()->name }}" required></div>
            <button class="btn btn-accent btn-block" @disabled($available < 100000)>Request payout</button>
            <p class="hint mt-1 mb-0">Minimum ₦1,000. Processed by our finance team via our licensed payment partner.</p>
        </form>
    </aside>
</div>
@endsection
