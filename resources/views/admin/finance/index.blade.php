@extends('layouts.admin')
@section('title', 'Finance')
@section('content')
<x-page-head title="Finance overview" subtitle="Double-entry ledger in integer kobo. Entries are append-only; corrections are reversing entries." />
<div class="alert {{ $balanced ? 'alert-success' : 'alert-error' }} mb-3"><i data-lucide="{{ $balanced ? 'check-circle-2' : 'alert-octagon' }}"></i><div>{{ $balanced ? 'Ledger balanced — total debits equal total credits.' : 'Ledger is OUT OF BALANCE. Stop payouts and investigate.' }}</div></div>
@if($pendingTx)<div class="alert alert-warn mb-3"><i data-lucide="clock"></i><div>{{ $pendingTx }} payments pending for 30+ minutes. <a class="link" href="{{ route('admin.finance.transactions', ['status' => 'pending']) }}">Re-check them with the provider</a>.</div></div>@endif
<div class="grid-3 mb-3">
    @foreach($accounts as $row)
        <div class="kpi"><div class="label">{{ $row['account']->name }}</div><div class="value money" style="font-size:1.5rem">{{ money($row['balance'], $row['account']->currency) }}</div><div class="sub mono">{{ $row['account']->code }} · {{ $row['account']->type }}</div></div>
    @endforeach
    <div class="kpi" style="background:var(--ink);color:var(--paper)"><div class="label" style="color:inherit;opacity:.7">Owed to professionals</div><div class="value money" style="font-size:1.5rem">{{ money($payables) }}</div><div class="sub" style="color:inherit;opacity:.7">sum of user payable accounts</div></div>
</div>
<div class="card"><h3>Confirmed payment volume</h3>
    @forelse($volume as $v)<div class="row-between mt-1"><span>{{ \App\Models\PaymentTransaction::PURPOSES[$v->purpose] ?? $v->purpose }} · {{ $v->n }} payments</span><strong class="money">{{ money($v->total) }}</strong></div>@empty<p class="muted mb-0">No confirmed payments yet.</p>@endforelse
</div>
@endsection
