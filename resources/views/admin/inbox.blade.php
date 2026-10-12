@extends('layouts.admin')
@section('title', 'Inbox')
@section('content')
<x-page-head title="Contact inbox" subtitle="Messages from the public contact form." />
<div class="tabs-pill mb-3">@foreach(['' => 'All', 'new' => 'New', 'replied' => 'Replied', 'closed' => 'Closed'] as $k => $v)<a href="{{ route('admin.inbox', ['status' => $k]) }}" @class(['is-active' => request('status', '') === $k])>{{ $v }}</a>@endforeach</div>
@forelse($messages as $m)
    <div class="card mb-2"><div class="row-between"><div><strong>{{ $m->name }}</strong> <a class="link small" href="mailto:{{ $m->email }}?subject=Re: your message to {{ setting('site_name') }}">{{ $m->email }}</a> <span class="badge">{{ $m->topic }}</span></div><span class="small muted">{{ $m->created_at->diffForHumans() }} <x-status :status="$m->status === 'new' ? 'pending' : $m->status" :label="ucfirst($m->status)" /></span></div>
        <p class="mt-1 mb-1" style="white-space:pre-wrap">{{ $m->message }}</p>
        <form method="POST" action="{{ route('admin.inbox.status', $m) }}" class="row">@csrf<button name="status" value="replied" class="btn btn-sm">Mark replied</button><button name="status" value="closed" class="btn btn-sm btn-ghost">Close</button></form></div>
@empty
    <x-empty icon="inbox" title="Inbox zero ✨" />
@endforelse
{{ $messages->links() }}
@endsection
