@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<x-page-head title="Account settings" />
<div class="grid-2" style="align-items:start">
    <form method="POST" action="{{ route('app.settings.update') }}" class="card">
        @csrf @method('PUT')
        <h3>Account</h3>
        <div class="field"><label for="name">Name</label><input id="name" class="input" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div class="field"><label for="email">Email</label><input id="email" type="email" class="input" name="email" value="{{ old('email', $user->email) }}" required>@if(! $user->email_verified_at)<span class="hint">Not verified yet.</span>@endif</div>
        <div class="field"><label for="phone">Phone (private)</label><input id="phone" class="input" name="phone" value="{{ old('phone', $user->phone) }}"></div>
        <label class="check mb-2"><input type="checkbox" name="email_notifications" value="1" @checked(($user->notification_prefs['email'] ?? true))> Email me notifications (we never put sensitive details in emails)</label>
        <button class="btn btn-primary">Save</button>
    </form>
    <form method="POST" action="{{ route('app.settings.password') }}" class="card">
        @csrf @method('PUT')
        <h3>Password</h3>
        <div class="field"><label for="current_password">Current password</label><input id="current_password" type="password" class="input" name="current_password" required></div>
        <div class="field"><label for="password">New password</label><input id="password" type="password" class="input" name="password" required minlength="8"></div>
        <div class="field"><label for="password_confirmation">Confirm</label><input id="password_confirmation" type="password" class="input" name="password_confirmation" required></div>
        <button class="btn btn-primary">Update password</button>
    </form>
    <div class="card card-soft">
        <h3>Workspaces</h3>
        <p class="small muted">One account, two hats. Switch any time from the sidebar.</p>
        <div class="row"><span class="badge {{ $user->is_talent ? 'badge-green' : '' }}">Talent {{ $user->is_talent ? '✓' : '' }}</span><span class="badge {{ $user->is_hirer ? 'badge-green' : '' }}">Hiring {{ $user->is_hirer ? '✓' : '' }}</span></div>
        @unless($user->is_talent && $user->is_hirer)<a class="btn btn-sm mt-2" href="{{ route('app.onboarding', ['want' => $user->is_talent ? 'hirer' : 'talent']) }}">Add {{ $user->is_talent ? 'hiring' : 'talent' }} workspace</a>@endunless
    </div>
    <div class="card card-soft">
        <h3>Your data</h3>
        <p class="small muted">Want a copy of your data or to close your account? Contact us and we’ll handle it under our privacy policy.</p>
        <a class="btn btn-sm" href="{{ route('contact', ['topic' => 'general']) }}">Contact privacy team</a>
    </div>
</div>
@endsection
