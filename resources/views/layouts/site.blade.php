@extends('layouts.base')

@section('body')
    @if(setting('announcement'))
        <div class="announce">{{ setting('announcement') }} <a href="{{ route('register') }}">Claim yours →</a></div>
    @endif
    <header class="site-nav" x-data="{ menu: false }" @keydown.escape.window="menu = false">
        <div class="container inner">
            @include('partials.brand')
            <nav class="nav-links" aria-label="Main">
                <a href="{{ route('talent.index') }}" @class(['active' => request()->routeIs('talent.*')])>Find talent</a>
                <a href="{{ route('jobs.index') }}" @class(['active' => request()->routeIs('jobs.*')])>Jobs</a>
                <a href="{{ route('projects.index') }}" @class(['active' => request()->routeIs('projects.*')])>Projects</a>
                <a href="{{ route('skills.index') }}" @class(['active' => request()->routeIs('skills.*')])>Assessments</a>
                <a href="{{ route('pricing') }}" @class(['active' => request()->routeIs('pricing')])>Pricing</a>
                @foreach($navPages->where('placement', 'header') as $p)
                    <a href="{{ route('pages.show', $p->slug) }}">{{ $p->title }}</a>
                @endforeach
            </nav>
            <div class="nav-cta">
                <button class="btn btn-ghost btn-icon hide-mobile" onclick="devhailTheme.toggle()" aria-label="Toggle dark mode"><i data-lucide="moon-star"></i></button>
                @auth
                    @if(auth()->user()->isStaff())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost hide-mobile">Admin</a>
                    @endif
                    <a href="{{ route('app.dashboard') }}" class="btn btn-primary">Dashboard <i data-lucide="arrow-up-right"></i></a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost hide-mobile">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-accent">Join free</a>
                @endauth
                <button class="btn btn-ghost btn-icon nav-toggle" @click="menu = !menu" :aria-expanded="menu" aria-label="Menu">
                    <span x-show="!menu"><i data-lucide="menu"></i></span><span x-show="menu" x-cloak><i data-lucide="x"></i></span>
                </button>
            </div>
        </div>
        <div class="mobile-menu" x-show="menu" x-cloak x-transition.opacity>
            <a href="{{ route('talent.index') }}">Find talent <i data-lucide="arrow-right"></i></a>
            <a href="{{ route('jobs.index') }}">Jobs <i data-lucide="arrow-right"></i></a>
            <a href="{{ route('projects.index') }}">Projects <i data-lucide="arrow-right"></i></a>
            <a href="{{ route('skills.index') }}">Assessments <i data-lucide="arrow-right"></i></a>
            <a href="{{ route('pricing') }}">Pricing <i data-lucide="arrow-right"></i></a>
            <a href="{{ route('how') }}">How it works <i data-lucide="arrow-right"></i></a>
            <a href="{{ route('blog.index') }}">Blog <i data-lucide="arrow-right"></i></a>
            @guest<a href="{{ route('login') }}">Log in <i data-lucide="arrow-right"></i></a>@endguest
            <div class="row mt-3"><button class="btn" onclick="devhailTheme.toggle()"><i data-lucide="moon-star"></i> Toggle theme</button></div>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="cols">
                <div>
                    @include('partials.brand')
                    <p class="mt-2" style="max-width:34ch;color:color-mix(in srgb,var(--paper) 72%,transparent)">{{ setting('site_tagline') }} Built in Nigeria for the world’s tech talent.</p>
                    <div class="socials mt-2">
                        @foreach(['social_x' => 'twitter', 'social_instagram' => 'instagram', 'social_linkedin' => 'linkedin', 'social_tiktok' => 'music-2'] as $key => $icon)
                            @if(setting($key))<a href="{{ setting($key) }}" target="_blank" rel="noopener" aria-label="{{ str_replace('social_', '', $key) }}"><i data-lucide="{{ $icon }}"></i></a>@endif
                        @endforeach
                        <a href="mailto:{{ setting('contact_email') }}" aria-label="Email"><i data-lucide="mail"></i></a>
                    </div>
                </div>
                <div>
                    <h4>For talent</h4>
                    <ul>
                        <li><a href="{{ route('register') }}">Create your profile</a></li>
                        <li><a href="{{ route('skills.index') }}">Take an assessment</a></li>
                        <li><a href="{{ route('jobs.index') }}">Browse jobs</a></li>
                        <li><a href="{{ route('projects.index') }}">Find projects</a></li>
                    </ul>
                </div>
                <div>
                    <h4>For hirers</h4>
                    <ul>
                        <li><a href="{{ route('talent.index') }}">Search verified talent</a></li>
                        <li><a href="{{ route('pricing') }}">Pricing</a></li>
                        <li><a href="{{ route('how') }}">How it works</a></li>
                        <li><a href="{{ route('register', ['as' => 'hirer']) }}">Post a job or project</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Devhail</h4>
                    <ul>
                        <li><a href="{{ route('blog.index') }}">Blog</a></li>
                        <li><a href="{{ route('faq') }}">FAQ</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                        @foreach($navPages->where('placement', 'footer') as $p)
                            <li><a href="{{ route('pages.show', $p->slug) }}">{{ $p->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="footer-giant" aria-hidden="true">{{ strtolower(setting('site_name')) }}.</div>
            <div class="row-between small" style="color:color-mix(in srgb,var(--paper) 55%,transparent)">
                <span>© {{ date('Y') }} {{ setting('site_name') }}. Skills &gt; vibes.</span>
                <span>Payments processed by licensed partners · Verified ≠ guaranteed, it’s evidence.</span>
            </div>
        </div>
    </footer>

    @include('partials.assistant')
@endsection
