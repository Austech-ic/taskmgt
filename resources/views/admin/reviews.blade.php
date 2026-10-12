@extends('layouts.admin')
@section('title', 'Reviews')
@section('content')
<x-page-head title="Reviews" subtitle="Only verified contracts can be reviewed. Hide abusive content; don’t erase legitimate criticism." />
<div class="tabs-pill mb-3">@foreach(['' => 'All', 'published' => 'Published', 'flagged' => 'Flagged', 'hidden' => 'Hidden'] as $k => $v)<a href="{{ route('admin.reviews.index', ['status' => $k]) }}" @class(['is-active' => request('status', '') === $k])>{{ $v }}</a>@endforeach</div>
@forelse($reviews as $r)
    <div class="card mb-2"><div class="row-between"><div><strong>{{ $r->reviewer->name }}</strong> → {{ $r->reviewee->name }} <span class="small muted">· {{ $r->contract->reference }} · {{ $r->created_at->format('j M Y') }}</span></div><span>{{ str_repeat('★', (int) round($r->overall)) }} <x-status :status="$r->status" /></span></div>
        <p class="small mt-1 mb-1">{{ $r->comment }}</p>
        <form method="POST" action="{{ route('admin.reviews.moderate', $r) }}" class="row">@csrf @foreach(['published' => 'Publish', 'flagged' => 'Flag', 'hidden' => 'Hide'] as $k => $v)<button name="status" value="{{ $k }}" class="btn btn-sm {{ $r->status === $k ? 'btn-primary' : '' }}">{{ $v }}</button>@endforeach</form></div>
@empty
    <x-empty icon="star" title="No reviews yet" />
@endforelse
{{ $reviews->links() }}
@endsection
