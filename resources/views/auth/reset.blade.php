@extends('layouts.auth')
@section('title', 'Choose a new password')
@section('content')
    <h1 style="font-size:2.2rem">New password, who dis?</h1>
    @if($errors->any())<div class="alert alert-error mb-2"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif
    <form method="POST" action="{{ route('password.update') }}" class="mt-3">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field"><label for="email">Email</label><input id="email" type="email" class="input" name="email" value="{{ old('email', $email) }}" required></div>
        <div class="field"><label for="password">New password</label><input id="password" type="password" class="input" name="password" required minlength="8"></div>
        <div class="field"><label for="password_confirmation">Confirm</label><input id="password_confirmation" type="password" class="input" name="password_confirmation" required></div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Update password</button>
    </form>
@endsection
