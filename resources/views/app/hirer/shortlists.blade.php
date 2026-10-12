@extends('layouts.app')
@section('title', 'Shortlists')
@section('content')
<x-page-head title="Shortlists" subtitle="Group candidates per role, leave notes, rate, compare and export." />
<div class="split">
    <div class="grid-2" style="align-content:start">
        @forelse($lists as $l)
            <a href="{{ route('app.shortlists.show', $l) }}" class="card card-hover"><div class="row-between"><h3 class="mb-0">{{ $l->name }}</h3><span class="badge badge-violet">{{ $l->items_count }}</span></div><p class="small muted mt-1 mb-0">{{ $l->description ?: 'No description' }}</p><p class="small muted mt-1 mb-0">by {{ $l->creator->name }} · {{ $l->created_at->diffForHumans() }}</p></a>
        @empty
            <div style="grid-column:1/-1"><x-empty icon="list-checks" title="No shortlists yet" text="Create one per role, then add people straight from search." /></div>
        @endforelse
    </div>
    <aside><form method="POST" action="{{ route('app.shortlists.store') }}" class="card card-pop">@csrf<h3>New shortlist</h3>
        <div class="field"><label for="name">Name</label><input id="name" class="input" name="name" placeholder="Senior React — Q4" required></div>
        <div class="field"><label for="description">Description</label><input id="description" class="input" name="description"></div>
        <button class="btn btn-primary btn-block">Create</button></form></aside>
</div>
@endsection
