@extends('layouts.site')
@section('title', $post->title)
@section('description', $post->meta_description ?: $post->excerpt)
@section('content')
<article class="section-tight">
    <div class="container container-sm">
        <a href="{{ route('blog.index') }}" class="small link">← Blog</a>
        <div style="font-size:3rem" class="mt-2">{{ $post->emoji ?: '📝' }}</div>
        @if($post->category)<span class="badge badge-violet">{{ $post->category }}</span>@endif
        <h1 class="mt-2" style="font-size:clamp(2rem,4.5vw,3rem)">{{ $post->title }}</h1>
        <div class="muted small">{{ $post->published_at?->format('j F Y') }} · {{ $post->read_minutes }} min read @if($post->author) · by {{ $post->author->name }}@endif</div>
        @if($post->coverUrl())<img src="{{ $post->coverUrl() }}" alt="" class="mt-3" style="border-radius:var(--radius-lg)">@endif
        <div class="prose mt-3">{!! \Illuminate\Support\Str::markdown($post->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
        <div class="row mt-4"><button class="btn btn-sm" data-copy="{{ route('blog.show', $post) }}"><i data-lucide="share-2"></i> Copy link</button></div>
    </div>
</article>
@if($more->isNotEmpty())
<section class="section-tight"><div class="container"><h2>Keep reading</h2><div class="grid-3">
    @foreach($more as $p)<a href="{{ route('blog.show', $p) }}" class="card card-hover"><div style="font-size:2rem">{{ $p->emoji ?: '📝' }}</div><h3 class="mt-1 mb-0">{{ $p->title }}</h3></a>@endforeach
</div></div></section>
@endif
@endsection
