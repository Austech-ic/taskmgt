@extends('layouts.base')
@section('title', 'Welcome')
@section('body')
<div class="install-shell">
    <div class="card card-pop" style="max-width:680px">
        <div class="row-between mb-2">@include('partials.brand')<span class="sticker">Let’s go ✦</span></div>
        @if(session('info'))<div class="alert alert-info mb-2"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>@endif
        @if($errors->any())<div class="alert alert-error mb-2"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif

        <div x-data="{ want: '{{ old('want', $want) }}' }">
            <h1 style="font-size:2rem">Hey {{ $user->firstName() }} 👋 What brings you here?</h1>
            <div class="radio-cards mt-2 mb-3">
                <label class="radio-card"><input type="radio" value="talent" x-model="want" @disabled($user->is_talent && $user->profile)><div class="card"><div class="icon-tile lime mb-1"><i data-lucide="sparkles"></i></div><strong>Get verified & hired</strong><div class="small muted">{{ $user->is_talent ? 'Already set up ✓' : 'Build a profile, prove skills, land work' }}</div></div></label>
                <label class="radio-card"><input type="radio" value="hirer" x-model="want" @disabled($user->is_hirer)><div class="card"><div class="icon-tile mb-1"><i data-lucide="building-2"></i></div><strong>Hire or start a project</strong><div class="small muted">{{ $user->is_hirer ? 'Already set up ✓' : 'Search talent, post jobs, fund milestones' }}</div></div></label>
            </div>

            <form method="POST" action="{{ route('app.onboarding.save') }}" x-show="want === 'talent'">
                @csrf
                <input type="hidden" name="want" value="talent">
                <div class="form-grid">
                    <div class="field span-2"><label for="primary_role">What’s your main role?</label>
                        <input id="primary_role" class="input" name="primary_role" list="roles" value="{{ old('primary_role') }}" placeholder="Start typing… e.g. Backend Engineer" required>
                        <datalist id="roles">@foreach($roles as $r)<option value="{{ $r }}">@endforeach</datalist></div>
                    <div class="field"><label for="city">City</label><input id="city" class="input" name="city" value="{{ old('city') }}" placeholder="Lagos"></div>
                    <div class="field"><label for="country">Country</label><input id="country" class="input" name="country" value="{{ old('country', 'Nigeria') }}" required></div>
                    <div class="field"><label for="years_experience">Years of experience</label><input id="years_experience" type="number" min="0" max="50" class="input" name="years_experience" value="{{ old('years_experience', 2) }}" required></div>
                    <div class="field"><label for="availability">Right now I’m…</label><select id="availability" class="select" name="availability">@foreach($availability as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                    <div class="field span-2"><label for="phone">Phone <span class="muted">(private — never shown publicly)</span></label><input id="phone" class="input" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+234…"></div>
                </div>
                <button class="btn btn-accent btn-lg btn-block" type="submit">Create my talent profile</button>
            </form>

            <form method="POST" action="{{ route('app.onboarding.save') }}" x-show="want === 'hirer'" x-cloak>
                @csrf
                <input type="hidden" name="want" value="hirer">
                <p class="muted">Search is free. Next we’ll set up your company (60 seconds), or jump straight into posting a project.</p>
                <div class="grid-2">
                    <button class="btn btn-primary btn-lg" name="intent" value="company" type="submit"><i data-lucide="building-2"></i> Set up company</button>
                    <button class="btn btn-lg" name="intent" value="project" type="submit"><i data-lucide="rocket"></i> Post a project</button>
                </div>
            </form>
        </div>
        <div class="row mt-3" style="justify-content:space-between">
            @if($user->is_talent || $user->is_hirer)<a class="small link" href="{{ route('app.dashboard') }}">← Back to dashboard</a>@else<span></span>@endif
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm">Log out</button></form>
        </div>
    </div>
</div>
@endsection
