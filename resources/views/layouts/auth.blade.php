@extends('layouts.base')

@section('body')
<div class="auth">
    <aside class="auth-art">
        <div class="blob blob-violet" style="opacity:.45"></div>
        <div class="blob blob-lime" style="opacity:.25"></div>
        @include('partials.brand')
        <div>
            <span class="sticker">✦ @yield('art_sticker', 'Skills > vibes')</span>
            <h2 class="mt-3">@yield('art_title', 'Your skills deserve receipts.')</h2>
            <p class="muted" style="max-width:40ch;color:color-mix(in srgb,var(--paper) 70%,transparent)">@yield('art_text', 'Prove what you can do, get discovered by teams that care, and get paid by milestone.')</p>
        </div>
        <div class="trust-row" style="color:color-mix(in srgb,var(--paper) 70%,transparent)">
            <span><i data-lucide="badge-check"></i> Tested skills</span>
            <span><i data-lucide="shield-check"></i> Milestone payments</span>
            <span><i data-lucide="lock"></i> Private contact info</span>
        </div>
    </aside>
    <main class="auth-form">
        <div class="box">
            <div class="mb-4 mobile-only">@include('partials.brand')</div>
            @yield('content')
        </div>
    </main>
</div>
@endsection
