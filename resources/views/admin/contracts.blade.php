@extends('layouts.admin')
@section('title', 'Contracts')
@section('content')
<x-page-head title="Contracts" subtitle="All marketplace contracts and their milestone states." />
<form class="row mb-3"><input class="input" style="max-width:260px" name="q" value="{{ request('q') }}" placeholder="Reference or title"><select class="select" name="status" style="width:auto"><option value="">Any status</option>@foreach(\App\Support\Labels::CONTRACT_STATUS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select><button class="btn">Filter</button></form>
<div class="table-wrap"><table class="table"><thead><tr><th>Contract</th><th>Parties</th><th class="right">Value</th><th>Milestones</th><th>Status</th></tr></thead><tbody>
@foreach($contracts as $c)
    <tr><td><a class="strong" href="{{ route('app.contracts.show', $c) }}">{{ $c->reference }}</a><div class="small muted">{{ $c->title }}</div></td><td class="small">{{ $c->client->name }} → {{ $c->professional->name }}</td><td class="right money">{{ money($c->amount_minor, $c->currency) }}</td>
        <td class="small">{{ $c->milestones->where('status', 'released')->count() }}/{{ $c->milestones->count() }} released</td><td><x-status :status="$c->status" :label="$c->statusLabel()" /></td></tr>
@endforeach
</tbody></table></div>
{{ $contracts->links() }}
@endsection
