@extends('layouts.app')
@section('title', 'Messages')
@section('content')
<x-page-head title="Messages" subtitle="Keep project chat on-platform — it’s your evidence trail if anything goes sideways." />
<div class="chat">
    <div class="chat-list {{ $active ? 'has-active' : '' }}">
        @forelse($conversations as $c)
            @php($other = $c->otherParticipant(auth()->user()))
            <a href="{{ route('app.messages.show', $c) }}" @class(['active' => $active?->id === $c->id])>
                <img class="avatar" src="{{ $other?->avatarUrl() }}" alt="">
                <div class="grow" style="min-width:0"><div class="row-between"><strong class="small truncate">{{ $other?->name ?? 'Conversation' }}</strong><span class="small muted">{{ $c->last_message_at?->shortAbsoluteDiffForHumans() }}</span></div>
                    <div class="small muted truncate">{{ $c->latestMessage?->body ?? $c->subject }}</div></div>
            </a>
        @empty
            <div style="padding:24px" class="center small muted">No conversations yet.</div>
        @endforelse
    </div>
    <div class="chat-thread">
        @if($active)
            @php($other = $active->otherParticipant(auth()->user()))
            <div class="row" style="padding:12px 16px;border-bottom:1px solid var(--line)"><a href="{{ route('app.messages.index') }}" class="btn btn-ghost btn-icon btn-sm mobile-only" aria-label="Back"><i data-lucide="arrow-left"></i></a><img class="avatar avatar-sm" src="{{ $other?->avatarUrl() }}" alt=""><div><strong>{{ $other?->name }}</strong><div class="small muted">{{ $active->subject }}</div></div></div>
            <div class="chat-messages" id="thread">
                @foreach($messages as $m)
                    <div class="bubble {{ $m->user_id === auth()->id() ? 'me' : 'them' }}">{{ $m->body }}@if($m->attachment_path)<br><a href="{{ route('app.messages.attachment', [$active, $m]) }}" style="text-decoration:underline">📎 {{ $m->attachment_name }}</a>@endif<span class="time">{{ $m->created_at->format('j M, g:ia') }}</span></div>
                @endforeach
                <span id="end"></span>
            </div>
            <form method="POST" action="{{ route('app.messages.send', $active) }}" enctype="multipart/form-data" class="chat-compose">
                @csrf
                <label class="btn btn-ghost btn-icon" style="cursor:pointer" aria-label="Attach file"><i data-lucide="paperclip"></i><input type="file" name="attachment" hidden></label>
                <textarea class="textarea" name="body" rows="1" style="min-height:44px" placeholder="Type a message…" maxlength="5000" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.requestSubmit();}"></textarea>
                <button class="btn btn-primary btn-icon" aria-label="Send"><i data-lucide="send"></i></button>
            </form>
            <script>document.getElementById('thread')?.scrollTo(0, 1e9);</script>
        @else
            <div style="display:grid;place-items:center;height:100%;padding:40px"><x-empty icon="messages-square" title="Pick a conversation" text="Chats open after a contact request is accepted, a profile is unlocked, or a contract starts." /></div>
        @endif
    </div>
</div>
@endsection
