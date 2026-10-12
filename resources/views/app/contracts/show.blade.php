@extends('layouts.app')
@section('title', $contract->title)
@section('content')
<a href="{{ route('app.contracts.index') }}" class="small link">← Contracts</a>
<x-page-head :title="$contract->title" :subtitle="$contract->reference.' · '.$contract->client->name.' → '.$contract->professional->name">
    <x-status :status="$contract->status" :label="$contract->statusLabel()" />
</x-page-head>

@if(setting('payment_provider') === 'sandbox' && $isClient)
    <div class="alert alert-warn mb-3"><i data-lucide="flask-conical"></i><div><strong>Test mode:</strong> milestone funding uses the sandbox checkout — no real money moves.</div></div>
@endif

@if($contract->status === 'pending_acceptance')
    @if($isPro)
        <div class="card card-pop mb-3">
            <h3>Review & accept this contract</h3>
            <p class="muted">Check the scope, milestones and acceptance criteria below. Once you accept, the client can fund milestones and you can start work. A {{ $contract->commissionPercent() }} marketplace fee applies to each payout.</p>
            <form method="POST" action="{{ route('app.contracts.accept', $contract) }}">@csrf
                <label class="check mb-2"><input type="checkbox" name="agree" value="1" required> I accept the scope, milestones, acceptance criteria and fees.</label>
                <button class="btn btn-accent">Accept contract 🤝</button></form>
        </div>
    @else
        <div class="alert alert-info mb-3"><i data-lucide="hourglass"></i><div>Waiting for {{ $contract->professional->firstName() }} to accept. You’ll fund milestones once they do.</div></div>
    @endif
@endif

<div class="split">
    <div class="stack" style="--stack:20px">
        @foreach($contract->milestones as $m)
            @php($b = $service->breakdown($m))
            @php($latest = $m->deliverables->first())
            <div class="card {{ in_array($m->status, ['submitted', 'disputed']) ? 'card-pop' : '' }}">
                <div class="row-between row-top">
                    <div><div class="small muted strong">MILESTONE {{ $m->position }}</div><h3 class="mb-0">{{ $m->title }}</h3>@if($m->description)<p class="small muted mt-1 mb-0">{{ $m->description }}</p>@endif</div>
                    <div class="right"><div style="font:800 1.4rem var(--display)" class="money">{{ money($m->amount_minor, $contract->currency) }}</div><x-status :status="$m->status" :label="$m->statusLabel()" /></div>
                </div>
                <div class="row small muted mt-1 gap-lg">
                    @if($m->due_on)<span><i data-lucide="calendar" style="display:inline;width:14px;vertical-align:-2px"></i> Due {{ $m->due_on->format('j M Y') }}</span>@endif
                    @if($m->funded_at)<span>Funded {{ $m->funded_at->format('j M') }}</span>@endif
                    @if($m->revisions_used)<span>{{ $m->revisions_used }}/{{ $maxRevisions }} revisions used</span>@endif
                    @if($isPro)<span>You receive <strong class="money">{{ money($b['payout'], $contract->currency) }}</strong> after fees</span>@endif
                    @if($isClient && $b['client_fee'])<span>You pay <strong class="money">{{ money($b['funding'], $contract->currency) }}</strong> incl. fee</span>@endif
                </div>

                @if($m->deliverables->isNotEmpty())
                    <div class="divider"></div>
                    @foreach($m->deliverables as $d)
                        <div class="card card-soft mb-1" style="padding:14px">
                            <div class="row-between small"><strong>Delivery v{{ $d->version }}</strong><span class="muted">{{ $d->created_at->format('j M, g:ia') }} @if($d->review_status !== 'pending')· <x-status :status="$d->review_status" />@endif</span></div>
                            <p class="small mt-1 mb-1" style="white-space:pre-wrap">{{ $d->note }}</p>
                            <div class="row small">@if($d->link)<a class="link" href="{{ $d->link }}" target="_blank" rel="noopener nofollow">Open link ↗</a>@endif @if($d->file_path)<a class="link" href="{{ route('app.contracts.download', [$contract, $d]) }}"><i data-lucide="paperclip" style="display:inline;width:14px"></i> {{ $d->file_name }}</a>@endif</div>
                            @if($d->review_note)<p class="small mt-1 mb-0"><strong>Client feedback:</strong> {{ $d->review_note }}</p>@endif
                        </div>
                    @endforeach
                @endif

                {{-- Actions --}}
                @if($isClient && $contract->status === 'active' && in_array($m->status, ['pending', 'funding_pending']))
                    <form method="POST" action="{{ route('app.contracts.fund', [$contract, $m]) }}" class="mt-2">@csrf<button class="btn btn-accent"><i data-lucide="wallet"></i> {{ $m->status === 'funding_pending' ? 'Complete payment' : 'Fund milestone' }} · {{ money($b['funding'], $contract->currency) }}</button></form>
                @endif
                @if($isPro && in_array($m->status, ['funded', 'revision_requested']))
                    <form method="POST" action="{{ route('app.contracts.deliver', [$contract, $m]) }}" enctype="multipart/form-data" class="mt-2" x-data="{ open: {{ $m->status === 'revision_requested' ? 'true' : 'false' }} }">
                        @csrf
                        <button type="button" class="btn btn-primary" @click="open = !open" x-show="!open"><i data-lucide="package-check"></i> Submit work</button>
                        <div x-show="open" x-cloak>
                            <div class="field"><label>What are you delivering?</label><textarea class="textarea" name="note" rows="4" required minlength="10" placeholder="Summary, how to test it, anything the client should know."></textarea></div>
                            <div class="form-grid"><div class="field"><label>Link</label><input type="url" class="input" name="link" placeholder="Staging URL, repo, Figma…"></div><div class="field"><label>File (max 20MB)</label><input type="file" class="input" name="file"></div></div>
                            <button class="btn btn-accent">Deliver</button>
                        </div>
                    </form>
                @endif
                @if($isClient && $m->status === 'submitted')
                    <div class="row mt-2" x-data="{ revise: false }">
                        <form method="POST" action="{{ route('app.contracts.approve', [$contract, $m]) }}" data-confirm="Approve and release {{ money($m->amount_minor, $contract->currency) }} to {{ $contract->professional->firstName() }}? This can’t be undone.">@csrf<button class="btn btn-accent"><i data-lucide="check-check"></i> Approve & release</button></form>
                        <button type="button" class="btn" @click="revise = !revise" @disabled($m->revisions_used >= $maxRevisions)><i data-lucide="pencil"></i> Request revision</button>
                        <form method="POST" action="{{ route('app.contracts.revise', [$contract, $m]) }}" x-show="revise" x-cloak class="w-full mt-1">@csrf
                            <textarea class="textarea" name="review_note" rows="3" required minlength="10" placeholder="Which acceptance criteria aren’t met yet? Be specific."></textarea>
                            <button class="btn btn-primary btn-sm mt-1">Send revision request</button></form>
                    </div>
                @endif
            </div>
        @endforeach

        @if($contract->disputes->isNotEmpty())
            <div class="card">
                <h3>Disputes</h3>
                @foreach($contract->disputes as $d)
                    <div class="card card-soft mb-1"><div class="row-between"><strong>{{ \App\Models\Dispute::REASONS[$d->reason] ?? $d->reason }}</strong><x-status :status="$d->status" /></div>
                        <p class="small mt-1 mb-1" style="white-space:pre-wrap">{{ $d->details }}</p><div class="small muted">Raised by {{ $d->raiser->name }} · {{ $d->created_at->format('j M Y') }}</div>
                        @if($d->status === 'resolved')<div class="alert alert-info mt-2"><i data-lucide="scale"></i><div><strong>Decision ({{ $d->outcome }}):</strong> {{ $d->resolution }}@if($d->release_minor !== null)<br>Released {{ money($d->release_minor, $contract->currency) }} · refunded {{ money($d->refund_minor ?? 0, $contract->currency) }}@endif</div></div>@endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($canReview)
            <div class="card">
                <h3>Reviews</h3>
                @foreach($contract->reviews as $r)
                    <div class="mt-1"><strong>{{ $r->reviewer->name }}</strong> <span>{{ str_repeat('★', (int) round($r->overall)) }}</span><p class="small mb-1">{{ $r->comment }}</p></div>
                @endforeach
                @if(! $myReview)
                    <form method="POST" action="{{ route('app.contracts.review', $contract) }}" class="mt-2">
                        @csrf
                        <p class="small muted">Honest, specific reviews make the whole platform better. Rate 1–5.</p>
                        <div class="form-grid">
                            @foreach(($isClient ? ['quality' => 'Quality of work', 'timeliness' => 'Timeliness', 'communication' => 'Communication', 'professionalism' => 'Professionalism', 'scope_compliance' => 'Stuck to scope'] : ['communication' => 'Communication', 'professionalism' => 'Professionalism']) as $k => $v)
                                <div class="field"><label>{{ $v }}</label><select class="select" name="{{ $k }}" required>@for($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>@endfor</select></div>
                            @endforeach
                        </div>
                        @if($isClient)<label class="check mb-2"><input type="checkbox" name="on_time" value="1" checked> Delivered on time</label>@endif
                        <div class="field"><textarea class="textarea" name="comment" rows="3" required minlength="20" placeholder="What was it like working together?"></textarea></div>
                        <button class="btn btn-primary">Post review</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <aside class="sticky stack" style="--stack:16px">
        <div class="card">
            <div class="small muted">Contract value</div>
            <div style="font:800 1.8rem var(--display)" class="money">{{ money($contract->amount_minor, $contract->currency) }}</div>
            <div class="meter green mt-1"><i style="width:{{ $contract->amount_minor ? round($contract->releasedTotal() / $contract->amount_minor * 100) : 0 }}%"></i></div>
            <div class="list-divided small mt-2">
                <div class="row-between"><span class="muted">Funded so far</span><strong class="money">{{ money($contract->fundedTotal(), $contract->currency) }}</strong></div>
                <div class="row-between"><span class="muted">Released</span><strong class="money">{{ money($contract->releasedTotal(), $contract->currency) }}</strong></div>
                <div class="row-between"><span class="muted">Marketplace fee</span><strong>{{ $contract->commissionPercent() }} · {{ ['professional' => 'from payout', 'client' => 'paid by client', 'split' => 'split'][$contract->commission_payer] }}</strong></div>
            </div>
            @php($other = $isClient ? $contract->professional : $contract->client)
            @if($isClient && $other->profile)
                <form method="POST" action="{{ route('app.messages.start', $other->profile) }}" class="mt-2">@csrf<button class="btn btn-block"><i data-lucide="message-square"></i> Message {{ $other->firstName() }}</button></form>
            @endif
        </div>
        <details class="card"><summary class="strong" style="cursor:pointer">Scope & acceptance criteria</summary>
            <p class="small mt-2" style="white-space:pre-wrap">{{ $contract->scope }}</p>
            @if($contract->acceptance_criteria)<h4 class="mt-2">Acceptance criteria</h4><p class="small" style="white-space:pre-wrap">{{ $contract->acceptance_criteria }}</p>@endif
        </details>
        @if(in_array($contract->status, ['active', 'disputed']))
            <details class="card card-soft"><summary class="strong" style="cursor:pointer">⚖️ Something wrong? Open a dispute</summary>
                <p class="small muted mt-1">Try talking it through first. A dispute pauses the milestone’s money while a neutral Devhail officer reviews the contract, deliverables and messages.</p>
                <form method="POST" action="{{ route('app.contracts.dispute', $contract) }}" data-confirm="Open a formal dispute?">@csrf
                    <div class="field"><label>Milestone</label><select class="select" name="milestone_id">@foreach($contract->milestones->whereIn('status', ['funded', 'submitted', 'revision_requested']) as $m)<option value="{{ $m->id }}">{{ $m->position }}. {{ $m->title }}</option>@endforeach</select></div>
                    <div class="field"><label>Reason</label><select class="select" name="reason">@foreach($disputeReasons as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                    <div class="field"><label>Details & evidence</label><textarea class="textarea" name="details" rows="4" required minlength="30"></textarea></div>
                    <button class="btn btn-danger btn-block">Open dispute</button></form>
            </details>
        @endif
    </aside>
</div>
@endsection
