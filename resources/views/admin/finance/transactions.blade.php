@extends('layouts.admin')
@section('title', 'Transactions')
@section('content')
<x-page-head title="Transactions" subtitle="Status changes only after server-side verification with the provider." />
<form class="row mb-3"><input class="input" style="max-width:260px" name="q" value="{{ request('q') }}" placeholder="Reference"><select class="select" name="status" style="width:auto"><option value="">Any status</option>@foreach(['pending', 'succeeded', 'failed', 'refunded'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select><button class="btn">Filter</button></form>
<div class="table-wrap"><table class="table"><thead><tr><th>Reference</th><th>Payer</th><th>Purpose</th><th class="right">Amount</th><th>Provider</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($transactions as $t)
    <tr><td class="mono small">{{ $t->reference }}<br><span class="muted">{{ $t->created_at->format('j M Y H:i') }}</span></td><td class="small">{{ $t->user->name }}@if($t->organization)<br><span class="muted">{{ $t->organization->name }}</span>@endif</td>
        <td class="small">{{ $t->purposeLabel() }}</td><td class="right money">{{ money($t->amount_minor, $t->currency) }}</td><td class="small">{{ $t->provider }}<br><span class="muted mono">{{ $t->provider_reference }}</span></td><td><x-status :status="$t->status" /></td>
        <td class="actions">@if($t->status === 'pending')<form method="POST" action="{{ route('admin.finance.recheck', $t) }}">@csrf<button class="btn btn-sm">Re-check</button></form>@endif</td></tr>
@endforeach
</tbody></table></div>
{{ $transactions->links() }}
@endsection
