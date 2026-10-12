@extends('layouts.app')
@section('title', 'Unlocked contacts')
@section('content')
<x-page-head title="Unlocked contacts" subtitle="Everyone your team has unlocked. Visible to your whole company."><span class="badge badge-lime">{{ $org->unlocksAvailable() }} unlocks left</span></x-page-head>
@if($unlocks->isEmpty())
    <x-empty icon="unlock" title="No unlocks yet" text="Find someone great and unlock their contact details from their profile."><a href="{{ route('talent.index') }}" class="btn btn-accent">Search talent</a></x-empty>
@else
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Professional</th><th>Contact</th><th>Unlocked by</th><th>When</th><th></th></tr></thead>
        <tbody>@foreach($unlocks as $u)
            <tr><td><a class="row" href="{{ route('talent.show', $u->profile) }}"><img class="avatar avatar-sm" src="{{ $u->profile->user->avatarUrl() }}" alt=""><span><strong>{{ $u->profile->user->name }}</strong><br><span class="small muted">{{ $u->profile->primary_role }}</span></span></a></td>
                <td class="small">@if($u->profile->allow_direct_contact){{ $u->profile->contact_email }}<br>{{ $u->profile->contact_phone }}@else<span class="muted">Contact via {{ setting('site_name') }} only</span>@endif</td>
                <td class="small">{{ $u->user->name }}<br><span class="muted">{{ $u->source === 'subscription' ? 'Plan allowance' : 'Credit' }}</span></td>
                <td class="small nowrap">{{ $u->created_at->format('j M Y') }}</td>
                <td class="actions"><form method="POST" action="{{ route('app.messages.start', $u->profile) }}">@csrf<button class="btn btn-sm"><i data-lucide="message-square"></i> Message</button></form></td></tr>
        @endforeach</tbody>
    </table></div>
    {{ $unlocks->links() }}
@endif
@endsection
