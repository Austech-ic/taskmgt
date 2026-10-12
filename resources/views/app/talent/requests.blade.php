@extends('layouts.app')
@section('title', 'Contact requests')
@section('content')
<x-page-head title="Contact requests" subtitle="Employers who want to talk. Accept to open a chat — your contact details stay private unless you share them." />
@forelse($requests as $r)
    <div class="card mb-2">
        <div class="row-between row-top">
            <div><strong>{{ $r->subject }}</strong><div class="small muted">{{ $r->organization->name }} @if($r->organization->isVerified())<span class="badge badge-violet">Verified company</span>@endif · {{ $r->sender->name }} · {{ $r->created_at->diffForHumans() }}</div></div>
            <x-status :status="$r->status" />
        </div>
        <p class="mt-2 mb-2" style="white-space:pre-wrap">{{ $r->message }}</p>
        @if($r->status === 'pending')
            <div class="row">
                <form method="POST" action="{{ route('app.requests.respond', [$r, 'accept']) }}">@csrf<button class="btn btn-accent btn-sm"><i data-lucide="message-square"></i> Accept & chat</button></form>
                <form method="POST" action="{{ route('app.requests.respond', [$r, 'decline']) }}">@csrf<button class="btn btn-ghost btn-sm">Decline</button></form>
            </div>
        @endif
    </div>
@empty
    <x-empty icon="inbox" title="No requests yet" text="When an employer wants to connect, it lands here. Verified badges = more requests 😉" />
@endforelse
{{ $requests->links() }}
@endsection
