@extends('layouts.admin')
@section('title', 'Dispute')
@section('content')
@php($c = $dispute->contract)
<a href="{{ route('admin.disputes.index') }}" class="small link">← Disputes</a>
<x-page-head :title="$c->reference.' · '.$c->title" :subtitle="'Client: '.$c->client->name.' · Professional: '.$c->professional->name" />
<div class="split">
    <div class="stack" style="--stack:20px">
        <div class="card card-pop"><div class="row-between"><h3 class="mb-0">{{ \App\Models\Dispute::REASONS[$dispute->reason] ?? $dispute->reason }}</h3><x-status :status="$dispute->status === 'resolved' ? 'resolved' : 'disputed'" :label="ucfirst($dispute->status)" /></div>
            <p class="small muted">Raised by {{ $dispute->raiser->name }} ({{ $dispute->raised_by === $c->client_id ? 'client' : 'professional' }}) · {{ $dispute->created_at->format('j M Y H:i') }}</p>
            <p style="white-space:pre-wrap">{{ $dispute->details }}</p></div>
        <div class="card"><h3>Scope & acceptance criteria</h3><p class="small" style="white-space:pre-wrap">{{ $c->scope }}</p>@if($c->acceptance_criteria)<h4>Acceptance criteria</h4><p class="small" style="white-space:pre-wrap">{{ $c->acceptance_criteria }}</p>@endif</div>
        <div class="card"><h3>Milestones & deliveries</h3>
            @foreach($c->milestones as $m)
                <div class="mt-2" style="padding:12px;border:1px solid var(--line);border-radius:12px;{{ $m->id === $dispute->milestone_id ? 'border-color:var(--ink)' : '' }}">
                    <div class="row-between"><strong>{{ $m->position }}. {{ $m->title }}</strong><span><span class="money">{{ money($m->amount_minor, $c->currency) }}</span> <x-status :status="$m->status" :label="$m->statusLabel()" /></span></div>
                    @foreach($m->deliverables as $d)<div class="small mt-1">v{{ $d->version }} · {{ $d->created_at->format('j M') }}: {{ \Illuminate\Support\Str::limit($d->note, 200) }} @if($d->file_path)<a class="link" href="{{ route('app.contracts.download', [$c, $d]) }}">file</a>@endif @if($d->review_note)<br><em>Client: {{ $d->review_note }}</em>@endif</div>@endforeach
                </div>
            @endforeach</div>
        <div class="card"><h3>Messages between the parties</h3>
            @forelse($messages as $msg)<div class="small mt-1"><strong>{{ $msg->user->name }}</strong> <span class="muted">{{ $msg->created_at->format('j M H:i') }}</span><br>{{ $msg->body }}</div>@empty<p class="muted small mb-0">No on-platform messages.</p>@endforelse</div>
    </div>
    <aside class="sticky">
        @if($dispute->status !== 'resolved')
            <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}" class="card card-pop" x-data="{ outcome: 'release' }" data-confirm="This settles the disputed money and can’t be undone. Continue?">
                @csrf
                <h3>Resolve</h3>
                @if($breakdown)<p class="small muted">Held for this milestone: <strong class="money">{{ money($breakdown['funding'], $c->currency) }}</strong> (milestone {{ money($dispute->milestone->amount_minor, $c->currency) }} @if($breakdown['client_fee'])+ client fee {{ money($breakdown['client_fee'], $c->currency) }}@endif). Commission applies only to the released part.</p>@endif
                <div class="field"><select class="select" name="outcome" x-model="outcome"><option value="release">Release in full to professional</option><option value="refund">Refund in full to client</option><option value="split">Split</option></select></div>
                <div class="field" x-show="outcome === 'split'" x-cloak><label>Release to professional (₦, of milestone amount)</label><input class="input" name="release_amount" inputmode="decimal"></div>
                <div class="field"><label>Written decision (both parties see this)</label><textarea class="textarea" name="resolution" rows="6" required minlength="20"></textarea></div>
                <button class="btn btn-primary btn-block">Resolve dispute</button>
                <p class="hint mt-1 mb-0">Refunds are recorded in the ledger; execute the actual refund in your payment provider’s dashboard.</p>
            </form>
        @else
            <div class="card"><h3>Resolved</h3><p class="small">{{ ucfirst($dispute->outcome) }} by {{ $dispute->resolver?->name }} on {{ $dispute->resolved_at?->format('j M Y') }}</p><p class="small">Released {{ money($dispute->release_minor ?? 0, $c->currency) }} · refunded {{ money($dispute->refund_minor ?? 0, $c->currency) }}</p><p class="small" style="white-space:pre-wrap">{{ $dispute->resolution }}</p></div>
        @endif
    </aside>
</div>
@endsection
