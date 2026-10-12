@extends('layouts.admin')
@section('title', 'Disputes')
@section('content')
<x-page-head title="Disputes" subtitle="Neutral, documented decisions. Unresolved allegations are never shown as fact." />
<div class="tabs-pill mb-3">@foreach(['open' => 'Open', 'resolved' => 'Resolved', 'all' => 'All'] as $k => $v)<a href="{{ route('admin.disputes.index', ['status' => $k]) }}" @class(['is-active' => request('status', 'open') === $k])>{{ $v }}</a>@endforeach</div>
@forelse($disputes as $d)
    <a href="{{ route('admin.disputes.show', $d) }}" class="card card-hover mb-2 row-between"><div><strong>{{ $d->contract->reference }} · {{ $d->contract->title }}</strong><div class="small muted">{{ \App\Models\Dispute::REASONS[$d->reason] ?? $d->reason }} · raised by {{ $d->raiser->name }} {{ $d->created_at->diffForHumans() }}</div></div><div class="right">@if($d->milestone)<div class="money strong">{{ money($d->milestone->amount_minor, $d->contract->currency) }}</div>@endif<x-status :status="$d->status === 'resolved' ? 'resolved' : 'disputed'" :label="ucfirst(str_replace('_', ' ', $d->status))" /></div></a>
@empty
    <x-empty icon="scale" title="No disputes" text="Peace and quiet 🕊️" />
@endforelse
{{ $disputes->links() }}
@endsection
