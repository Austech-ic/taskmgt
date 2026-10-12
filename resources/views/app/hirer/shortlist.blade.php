@extends('layouts.app')
@section('title', $list->name)
@section('content')
<a href="{{ route('app.shortlists.index') }}" class="small link">← Shortlists</a>
<x-page-head title="{{ $list->name }}" subtitle="{{ $list->description }}">
    @if($items->count() > 1)<a class="btn" href="{{ route('app.compare', ['ids' => $items->take(4)->pluck('professional_profile_id')->implode(',')]) }}"><i data-lucide="columns-3"></i> Compare</a>@endif
    <a class="btn" href="{{ route('app.shortlists.export', $list) }}"><i data-lucide="download"></i> Export CSV</a>
    <form method="POST" action="{{ route('app.shortlists.destroy', $list) }}" data-confirm="Delete this shortlist?">@csrf @method('DELETE')<button class="btn btn-danger btn-icon" aria-label="Delete"><i data-lucide="trash-2"></i></button></form>
</x-page-head>
@forelse($items as $item)
    @php($p = $item->profile)
    <div class="card mb-2" x-data="{ notes: false }">
        <div class="row">
            <img class="avatar avatar-lg" src="{{ $p->user->avatarUrl() }}" alt="">
            <div class="grow"><a class="strong" href="{{ route('talent.show', $p) }}">{{ $p->user->name }}</a> @if($org->hasUnlocked($p))<span class="badge badge-green"><i data-lucide="unlock"></i> Unlocked</span>@endif
                <div class="small muted">{{ $p->primary_role }} · {{ $p->location() }} · {{ $p->years_experience }} yrs</div>
                <div class="chips mt-1">@foreach($p->verifiedSkills()->take(4) as $s)<span class="chip chip-verified">{{ $s->name }} L{{ $s->pivot->verified_level }}</span>@endforeach</div></div>
            <div class="row"><span class="small">{{ $item->rating ? str_repeat('★', $item->rating) : '' }}</span><button class="btn btn-sm" @click="notes = !notes"><i data-lucide="notebook-pen"></i> Notes</button>
                <form method="POST" action="{{ route('app.shortlists.items.remove', [$list, $item]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost btn-icon" aria-label="Remove"><i data-lucide="x"></i></button></form></div>
        </div>
        @if($item->note)<p class="small mt-2 mb-0 ink-2" style="white-space:pre-wrap" x-show="!notes">📝 {{ $item->note }}</p>@endif
        <form method="POST" action="{{ route('app.shortlists.items.update', [$list, $item]) }}" class="mt-2" x-show="notes" x-cloak>
            @csrf @method('PUT')
            <div class="row" style="flex-wrap:nowrap;align-items:flex-start"><textarea class="textarea" name="note" rows="2" placeholder="Internal notes — never shown to the candidate">{{ $item->note }}</textarea>
                <select class="select" name="rating" style="width:auto"><option value="">Rate</option>@for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected($item->rating === $i)>{{ str_repeat('★', $i) }}</option>@endfor</select>
                <button class="btn btn-primary btn-sm">Save</button></div>
        </form>
    </div>
@empty
    <x-empty icon="user-plus" title="Empty list" text="Add people from search using the bookmark button."><a href="{{ route('talent.index') }}" class="btn btn-accent">Search talent</a></x-empty>
@endforelse
@endsection
