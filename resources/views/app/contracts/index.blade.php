@extends('layouts.app')
@section('title', 'Contracts')
@section('content')
<x-page-head title="Contracts" :subtitle="$asClient ? 'Work you’ve hired for.' : 'Work you’ve been hired for.'" />
@if($contracts->isEmpty())
    <x-empty icon="file-signature" title="No contracts yet" :text="$asClient ? 'Hire from a project’s proposals to start a contract.' : 'Win a proposal and your contract shows up here.'" />
@else
    <div class="stack" style="--stack:14px">
        @foreach($contracts as $c)
            @php($released = $c->releasedTotal())
            <a href="{{ route('app.contracts.show', $c) }}" class="card card-hover">
                <div class="row-between"><div><strong>{{ $c->title }}</strong><div class="small muted">{{ $c->reference }} · with {{ $asClient ? $c->professional->name : $c->client->name }}</div></div><x-status :status="$c->status" :label="$c->statusLabel()" /></div>
                <div class="row-between small mt-2"><span>{{ $c->milestones->where('status', 'released')->count() }} / {{ $c->milestones->count() }} milestones paid</span><span class="money strong">{{ money($released, $c->currency) }} of {{ money($c->amount_minor, $c->currency) }}</span></div>
                <div class="meter green mt-1"><i style="width:{{ $c->amount_minor ? round($released / $c->amount_minor * 100) : 0 }}%"></i></div>
            </a>
        @endforeach
    </div>
    {{ $contracts->links() }}
@endif
@endsection
