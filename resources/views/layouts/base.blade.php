<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = setting('site_name');
        $pageTitle = trim($__env->yieldContent('title'));
        $metaDescription = trim($__env->yieldContent('description')) ?: setting('site_description');
    @endphp
    <title>{{ $pageTitle ? $pageTitle.' · '.$siteName : $siteName.' — '.setting('site_tagline') }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDescription), 160) }}">
    <meta property="og:title" content="{{ $pageTitle ?: $siteName }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDescription), 160) }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#0e0e14">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%230e0e14'/%3E%3Cpath d='M36 8 16 36h14l-4 20 22-30H34z' fill='%23c6ff3d'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <style>:root{--brand-primary: {{ preg_match('/^#[0-9a-f]{6}$/i', setting('brand_primary')) ? setting('brand_primary') : '#6d4aff' }};--brand-accent: {{ preg_match('/^#[0-9a-f]{6}$/i', setting('brand_accent')) ? setting('brand_accent') : '#c6ff3d' }};}</style>
    <script>try{var t=localStorage.getItem('dh-theme');if(t&&t!=='system')document.documentElement.setAttribute('data-theme',t);}catch(e){}</script>
    <script>window.DEVHAIL = { aiUrl: @json(auth()->check() ? route('app.ai', '__FEATURE__') : ''), assistantUrl: @json(route('assistant')) };</script>
    <script defer src="{{ asset('vendor/lucide.min.js') }}"></script>
    <script defer src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script defer src="{{ asset('vendor/alpine.min.js') }}"></script>
    @stack('head')
    @if(setting('analytics_snippet') && ! request()->is('admin*', 'install*'))
        {!! setting('analytics_snippet') !!}
    @endif
</head>
<body class="@yield('body_class')">
    @yield('body')
    @include('partials.toasts')
    @stack('scripts')
</body>
</html>
