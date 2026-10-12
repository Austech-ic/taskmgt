@extends('layouts.admin')
@section('title', 'Payouts')
@section('content')
<x-page-head title="Payouts" subtitle="Dual control: one officer approves, another confirms the transfer reference." />
<div class="tabs-pill mb-3">@foreach(['' => 'Open', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $k => $v)<a href="{{ route('admin.finance.payouts', ['status' => $k]) }}" @class(['is-active' => request('status', '') === $k])>{{ $v }}</a>@endforeach</div>
@forelse($payouts as $p)
    <div class="card mb-2">
        <div class="row-between"><div><strong>{{ $p->user->name }}</strong> <span class="small muted">{{ $p->user->email }}</span><div class="small">{{ $p->bank_name }} · {{ $p->account_number }} · {{ $p->account_name }}</div><div class="small muted">Requested {{ $p->created_at->diffForHumans() }} @if($p->approver)· approved by {{ $p->approver->name }}@endif @if($p->payout_reference)· ref {{ $p->payout_reference }}@endif</div></div>
            <div class="right"><div style="font:800 1.4rem var(--display)" class="money">{{ money($p->amount_minor, $p->currency) }}</div><x-status :status="$p->status" /></div></div>
        @if(in_array($p->status, ['requested', 'approved']))
            <form method="POST" action="{{ route('admin.finance.payouts.action', $p) }}" class="row mt-2">
                @csrf
                @if($p->status === 'requested')<button name="action" value="approve" class="btn btn-sm btn-primary">Approve</button>@endif
                @if($p->status === 'approved')<input class="input" name="payout_reference" placeholder="Transfer reference" style="max-width:220px"><button name="action" value="pay" class="btn btn-sm btn-accent">Mark paid</button>@endif
                <input class="input" name="note" placeholder="Note / rejection reason" style="max-width:260px"><button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
            </form>
        @endif
    </div>
@empty
    <x-empty icon="banknote" title="Nothing to process" />
@endforelse
{{ $payouts->links() }}
@endsection
