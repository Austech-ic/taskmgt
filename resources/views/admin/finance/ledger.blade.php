@extends('layouts.admin')
@section('title', 'Ledger')
@section('content')
<x-page-head title="Ledger" subtitle="Every money movement as balanced journal lines." />
<form class="row mb-3"><select class="select" name="account" style="max-width:420px" onchange="this.form.submit()"><option value="">All accounts</option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected(request('account') == $a->id)>{{ $a->code }} — {{ $a->name }}</option>@endforeach</select></form>
<div class="table-wrap"><table class="table"><thead><tr><th>When</th><th>Journal</th><th>Account</th><th>Memo</th><th class="right">Debit</th><th class="right">Credit</th></tr></thead><tbody>
@foreach($entries as $e)
    <tr><td class="small nowrap">{{ $e->created_at->format('j M Y H:i') }}</td><td class="mono small">{{ substr($e->journal_id, 0, 8) }}</td><td class="small"><span class="mono">{{ $e->account->code }}</span></td><td class="small">{{ $e->memo }}</td>
        <td class="right money">{{ $e->debit_minor ? money($e->debit_minor, $e->currency) : '' }}</td><td class="right money">{{ $e->credit_minor ? money($e->credit_minor, $e->currency) : '' }}</td></tr>
@endforeach
</tbody></table></div>
{{ $entries->links() }}
@endsection
