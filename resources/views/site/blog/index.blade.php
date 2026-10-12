@extends('layouts.site')
@section('title', 'Blog')
@section('description', 'Career tips, hiring playbooks and product updates from '.setting('site_name').'.')
@section('content')
<section class="section-tight">
    <div class="container">
        <span class="eyebrow">The {{ setting('site_name') }} blog</span>
        <h1 style="font-size:clamp(2rem,4.5vw,3.2rem)">Big brain energy, <span class="highlight">bite-sized.</span></h1>
        <div class="chips mt-2">
            <a href="{{ route('blog.index') }}" @class(['chip', 'chip-active' => ! request('category')])>All</a>
            @foreach($categories as $c)<a href="{{ route('blog.index', ['category' => $c]) }}" @class(['chip', 'chip-active' => request('category') === $c])>{{ $c }}</a>@endforeach
        </div>
        <div class="grid-3 mt-3">
            @forelse($posts as $post)
                <a href="{{ route('blog.show', $post) }}" class="card card-hover" style="display:flex;flex-direction:column;gap:10px">
                    @if($post->coverUrl())<img src="{{ $post->coverUrl() }}" alt="" style="border-radius:14px;aspect-ratio:16/9;object-fit:cover">@else<div style="font-size:2.6rem">{{ $post->emoji ?: '📝' }}</div>@endif
                    @if($post->category)<span class="badge badge-violet" style="align-self:flex-start">{{ $post->category }}</span>@endif
                    <h3 class="mb-0">{{ $post->title }}</h3>
                    <p class="small muted clamp-3 mb-0">{{ $post->excerpt }}</p>
                    <div class="small muted" style="margin-top:auto">{{ $post->published_at->format('j M Y') }} · {{ $post->read_minutes }} min read</div>
                </a>
            @empty
                <div style="grid-column:1/-1"><x-empty icon="newspaper" title="No posts yet" text="Fresh takes coming soon." /></div>
            @endforelse
        </div>
        {{ $posts->links() }}
    </div>
</section>
@endsection
