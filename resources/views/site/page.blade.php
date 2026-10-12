@extends('layouts.site')
@section('title', $page->meta_title ?: $page->title)
@section('description', $page->meta_description ?: $page->excerpt)
@section('content')
<section class="section-tight">
    <div class="container container-sm">
        <span class="eyebrow">{{ setting('site_name') }}</span>
        <h1 style="font-size:clamp(2rem,4.5vw,3rem)">{{ $page->title }}</h1>
        <div class="prose mt-3">{!! \Illuminate\Support\Str::markdown($page->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
    </div>
</section>
@endsection
