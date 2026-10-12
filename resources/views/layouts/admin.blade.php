@extends('layouts.base')

@php
    $user = auth()->user();
    $pendingAttempts = \App\Models\AssessmentAttempt::where('status', 'under_review')->count();
    $pendingTalent = \App\Models\ProfessionalProfile::where('status', 'under_review')->count();
    $openDisputes = \App\Models\Dispute::where('status', '!=', 'resolved')->count();
    $pendingPayouts = \App\Models\PayoutRequest::whereIn('status', ['requested', 'approved'])->count();
    $newMessages = \App\Models\ContactMessage::where('status', 'new')->count();
    $item = fn ($href, $icon, $label, $active, $count = 0) => compact('href', 'icon', 'label', 'active', 'count');
    $groups = [];
    $groups['Overview'] = [$item(route('admin.dashboard'), 'gauge', 'Dashboard', request()->routeIs('admin.dashboard'))];
    if ($user->hasStaffRole('reviewer', 'admin')) {
        $groups['Verification'] = [
            $item(route('admin.attempts.index'), 'clipboard-check', 'Review queue', request()->routeIs('admin.attempts.*'), $pendingAttempts),
            $item(route('admin.assessments.index'), 'book-open-check', 'Assessments', request()->routeIs('admin.assessments.*')),
        ];
    }
    if ($user->hasStaffRole('admin')) {
        $groups['Verification'][] = $item(route('admin.talent.index'), 'user-check', 'Talent approvals', request()->routeIs('admin.talent.*'), $pendingTalent);
        $groups['Marketplace'] = [
            $item(route('admin.users.index'), 'users', 'Users & roles', request()->routeIs('admin.users.*')),
            $item(route('admin.organizations.index'), 'building-2', 'Companies', request()->routeIs('admin.organizations.*')),
            $item(route('admin.listings.index'), 'briefcase', 'Jobs & projects', request()->routeIs('admin.listings.*')),
            $item(route('admin.reviews.index'), 'star', 'Reviews', request()->routeIs('admin.reviews.*')),
        ];
    }
    if ($user->hasStaffRole('finance', 'admin')) {
        $groups['Money'] = [
            $item(route('admin.finance.index'), 'landmark', 'Finance overview', request()->routeIs('admin.finance.index')),
            $item(route('admin.finance.transactions'), 'arrow-left-right', 'Transactions', request()->routeIs('admin.finance.transactions')),
            $item(route('admin.finance.ledger'), 'book-text', 'Ledger', request()->routeIs('admin.finance.ledger')),
            $item(route('admin.finance.payouts'), 'banknote', 'Payouts', request()->routeIs('admin.finance.payouts'), $pendingPayouts),
            $item(route('admin.contracts.index'), 'file-signature', 'Contracts', request()->routeIs('admin.contracts.*')),
            $item(route('admin.disputes.index'), 'scale', 'Disputes', request()->routeIs('admin.disputes.*'), $openDisputes),
        ];
    }
    if ($user->hasStaffRole('admin')) {
        $r = fn ($res, $icon, $label) => $item(route('admin.resources.index', $res), $icon, $label, request()->is('admin/r/'.$res.'*'));
        $groups['Catalogue'] = [$r('categories', 'shapes', 'Skill categories'), $r('skills', 'tags', 'Skills'), $r('plans', 'gem', 'Plans')];
        $groups['Content (CMS)'] = [
            $r('pages', 'file-text', 'Pages'), $r('posts', 'newspaper', 'Blog posts'), $r('faqs', 'circle-help', 'FAQs'), $r('testimonials', 'quote', 'Testimonials'),
            $item(route('admin.inbox'), 'inbox', 'Contact inbox', request()->routeIs('admin.inbox'), $newMessages),
        ];
        $groups['System'] = [
            $item(route('admin.settings'), 'sliders-horizontal', 'Settings', request()->routeIs('admin.settings*')),
            $item(route('admin.audit'), 'scroll-text', 'Audit log', request()->routeIs('admin.audit')),
        ];
    }
@endphp

@section('body')
<div class="app" x-data="{ side: false }">
    <div class="scrim" x-show="side" x-cloak @click="side = false"></div>
    <aside class="sidebar" :class="{ open: side }" aria-label="Admin navigation">
        @include('partials.brand')
        <div style="padding:0 10px 6px"><span class="badge badge-ink"><i data-lucide="shield"></i> {{ \App\Models\User::STAFF_ROLES[$user->staff_role] ?? 'Staff' }}</span></div>
        @foreach($groups as $group => $links)
            <div class="nav-group">{{ $group }}</div>
            @foreach($links as $l)
                <a href="{{ $l['href'] }}" @class(['side-link', 'active' => $l['active']])><i data-lucide="{{ $l['icon'] }}"></i> {{ $l['label'] }} @if($l['count'])<span class="count">{{ $l['count'] }}</span>@endif</a>
            @endforeach
        @endforeach
        <div class="side-foot">
            <a href="{{ route('app.dashboard') }}" class="side-link"><i data-lucide="layout-dashboard"></i> Member app</a>
            <a href="{{ route('home') }}" class="side-link" target="_blank"><i data-lucide="globe"></i> View site</a>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="btn btn-ghost btn-icon menu-btn" @click="side = true" aria-label="Open menu"><i data-lucide="panel-left"></i></button>
            <strong class="hide-mobile">Command centre</strong>
            <div class="grow"></div>
            <button class="btn btn-ghost btn-icon" onclick="devhailTheme.toggle()" aria-label="Toggle dark mode"><i data-lucide="moon-star"></i></button>
            <img class="avatar avatar-sm" src="{{ $user->avatarUrl() }}" alt="">
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm" type="submit">Log out</button></form>
        </header>
        <div class="page">
            @if($errors->any())
                <div class="alert alert-error mb-3"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
@endsection
