@extends('layouts.app')
@section('title', 'Portfolio')
@section('content')
<x-page-head title="Portfolio" subtitle="Depth beats volume. Show your role, the stack, and what changed because of your work." />
@include('app.talent.profile._nav')
<div class="split">
    <div class="grid-2" style="align-content:start">
        @forelse($profile->portfolio as $item)
            <div class="card" x-data="{ edit: false }">
                @if($item->image_path)<img src="{{ Storage::disk('public')->url($item->image_path) }}" alt="" style="border-radius:12px;aspect-ratio:16/9;object-fit:cover;width:100%;margin-bottom:12px">@endif
                <div class="row-between"><h3 class="mb-0">{{ $item->title }}</h3>@if($item->verified_at)<span class="badge badge-green">Verified</span>@endif</div>
                <p class="small muted">{{ $item->role }}</p>
                <p class="small">{{ \Illuminate\Support\Str::limit($item->description, 160) }}</p>
                <div class="row"><button class="btn btn-sm" @click="edit = !edit"><i data-lucide="pencil"></i> Edit</button>
                    <form method="POST" action="{{ route('app.profile.portfolio.delete', $item) }}" data-confirm="Delete this project?">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i data-lucide="trash-2"></i></button></form></div>
                <div x-show="edit" x-cloak class="mt-2">@include('app.talent.profile._portfolio-form', ['item' => $item])</div>
            </div>
        @empty
            <div style="grid-column:1/-1"><x-empty icon="folder-open" title="Show us what you’ve built" text="One great project with real outcomes beats ten links." /></div>
        @endforelse
    </div>
    <aside class="sticky"><div class="card card-pop"><h3>Add a project</h3>@include('app.talent.profile._portfolio-form', ['item' => null])</div></aside>
</div>
@endsection
