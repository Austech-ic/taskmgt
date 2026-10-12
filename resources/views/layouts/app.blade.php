@extends('layouts.base')

@php
    $user = auth()->user();
    $ws = workspace();
    $unreadNotes = $user->unreadNotifications()->count();
    $unreadMsgs = $user->unreadMessagesCount();
    $link = fn ($route, $icon, $label, $active = null, $count = 0) => [
        'href' => route($route), 'icon' => $icon, 'label' => $label,
        'active' => request()->routeIs($active ?? $route), 'count' => $count,
    ];
    $groups = $ws === 'hirer'
        ? [
            'Hire' => [
                $link('app.dashboard', 'layout-dashboard', 'Overview'),
                ['href' => route('talent.index'), 'icon' => 'search', 'label' => 'Find talent', 'active' => request()->routeIs('talent.*'), 'count' => 0],
                $link('app.shortlists.index', 'list-checks', 'Shortlists', 'app.shortlists.*'),
                $link('app.unlocks.index', 'unlock', 'Unlocked contacts'),
                $link('app.jobs.index', 'briefcase', 'Jobs & pipeline', 'app.jobs.*'),
                $link('app.projects.index', 'folder-kanban', 'Projects', 'app.projects.*'),
                $link('app.contracts.index', 'file-signature', 'Contracts', 'app.contracts.*'),
            ],
            'Account' => [
                $link('app.messages.index', 'messages-square', 'Messages', 'app.messages.*', $unreadMsgs),
                $link('app.billing', 'credit-card', 'Billing & plans'),
                $link('app.company.edit', 'building-2', 'Company'),
                $link('app.settings', 'settings', 'Settings'),
            ],
        ]
        : [
            'Grow' => [
                $link('app.dashboard', 'layout-dashboard', 'Overview'),
                $link('app.profile.edit', 'user-round', 'My profile', 'app.profile.*'),
                $link('app.assessments.index', 'badge-check', 'Assessments', ['app.assessments.*', 'app.attempts.*']),
            ],
            'Work' => [
                ['href' => route('jobs.index'), 'icon' => 'briefcase', 'label' => 'Browse jobs', 'active' => false, 'count' => 0],
                $link('app.applications.index', 'send', 'Applications'),
                ['href' => route('projects.index'), 'icon' => 'folder-search', 'label' => 'Browse projects', 'active' => false, 'count' => 0],
                $link('app.proposals.index', 'file-text', 'Proposals'),
                $link('app.contracts.index', 'file-signature', 'Contracts', 'app.contracts.*'),
                $link('app.earnings', 'wallet', 'Earnings'),
            ],
            'Account' => [
                $link('app.messages.index', 'messages-square', 'Messages', 'app.messages.*', $unreadMsgs),
                $link('app.requests.index', 'inbox', 'Contact requests'),
                $link('app.settings', 'settings', 'Settings'),
            ],
        ];
@endphp

@section('body')
<div class="app" x-data="{ side: false }">
    <div class="scrim" x-show="side" x-cloak @click="side = false"></div>
    <aside class="sidebar" :class="{ open: side }" aria-label="App navigation">
        @include('partials.brand')
        @if($user->is_talent && $user->is_hirer)
            <div class="ws-switch">
                <form method="POST" action="{{ route('app.workspace', 'talent') }}">@csrf<button @class(['is-active' => $ws === 'talent'])><i data-lucide="sparkles"></i> Talent</button></form>
                <form method="POST" action="{{ route('app.workspace', 'hirer') }}">@csrf<button @class(['is-active' => $ws === 'hirer'])><i data-lucide="building-2"></i> Hiring</button></form>
            </div>
        @endif
        @foreach($groups as $group => $links)
            <div class="nav-group">{{ $group }}</div>
            @foreach($links as $l)
                <a href="{{ $l['href'] }}" @class(['side-link', 'active' => $l['active']])>
                    <i data-lucide="{{ $l['icon'] }}"></i> {{ $l['label'] }}
                    @if($l['count'])<span class="count">{{ $l['count'] }}</span>@endif
                </a>
            @endforeach
        @endforeach
        <div class="side-foot">
            @if(! ($user->is_talent && $user->is_hirer))
                <a href="{{ route('app.onboarding', ['want' => $user->is_talent ? 'hirer' : 'talent']) }}" class="side-link"><i data-lucide="plus-circle"></i> {{ $user->is_talent ? 'Start hiring too' : 'Join as talent too' }}</a>
            @endif
            @if($user->isStaff())
                <a href="{{ route('admin.dashboard') }}" class="side-link"><i data-lucide="shield"></i> Admin console</a>
            @endif
            <a href="{{ route('home') }}" class="side-link"><i data-lucide="globe"></i> Public site</a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="btn btn-ghost btn-icon menu-btn" @click="side = true" aria-label="Open menu"><i data-lucide="panel-left"></i></button>
            <div class="grow"></div>
            <button class="btn btn-ghost btn-icon" onclick="devhailTheme.toggle()" aria-label="Toggle dark mode"><i data-lucide="moon-star"></i></button>
            <a href="{{ route('app.notifications') }}" class="btn btn-ghost btn-icon bell" aria-label="Notifications">
                <i data-lucide="bell"></i>@if($unreadNotes)<span class="ping"></span>@endif
            </a>
            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
                <button class="btn btn-ghost" style="padding:0 6px 0 4px" @click="open = !open" :aria-expanded="open">
                    <img class="avatar avatar-sm" src="{{ $user->avatarUrl() }}" alt="">
                    <span class="strong hide-mobile" style="font-size:.9rem">{{ $user->firstName() }}</span>
                    <i data-lucide="chevron-down"></i>
                </button>
                <div class="dropdown-menu" x-show="open" x-cloak x-transition>
                    <div style="padding:8px 12px"><strong>{{ $user->name }}</strong><div class="small muted truncate">{{ $user->email }}</div></div>
                    <div class="divider" style="margin:6px 0"></div>
                    @if($user->profile)
                        <a href="{{ route('talent.show', $user->profile) }}"><i data-lucide="external-link"></i> View public profile</a>
                    @endif
                    <a href="{{ route('app.settings') }}"><i data-lucide="settings"></i> Settings</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><i data-lucide="log-out"></i> Log out</button></form>
                </div>
            </div>
        </header>
        <div class="page">
            @if($errors->any() && ! View::hasSection('hide_errors'))
                <div class="alert alert-error mb-3"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
@include('partials.assistant')
@endsection
