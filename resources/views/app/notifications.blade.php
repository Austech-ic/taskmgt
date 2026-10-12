@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<x-page-head title="Notifications">@if(auth()->user()->unreadNotifications()->exists())<form method="POST" action="{{ route('app.notifications.read') }}">@csrf<button class="btn btn-sm"><i data-lucide="check-check"></i> Mark all read</button></form>@endif</x-page-head>
<div class="card card-flush">
    @forelse($notifications as $n)
        <a href="{{ route('app.notifications.open', $n->id) }}" class="row row-top" style="padding:16px 20px;border-bottom:1px solid var(--line);{{ $n->read_at ? 'opacity:.65' : '' }}">
            <span class="icon-tile sm"><i data-lucide="{{ $n->data['icon'] ?? 'bell' }}"></i></span>
            <div class="grow"><strong>{{ $n->data['title'] ?? 'Update' }}</strong><div class="small ink-2">{{ $n->data['body'] ?? '' }}</div><div class="small muted">{{ $n->created_at->diffForHumans() }}</div></div>
            @unless($n->read_at)<span class="notice-dot"></span>@endunless
        </a>
    @empty
        <div style="padding:24px"><x-empty icon="bell" title="All quiet" text="Assessment results, applicant updates and payments show up here." /></div>
    @endforelse
</div>
{{ $notifications->links() }}
@endsection
