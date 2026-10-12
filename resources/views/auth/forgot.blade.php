@extends('layouts.auth')
@section('title', 'Reset password')
@section('art_title', 'Happens to the best of us.')
@section('content')
    <h1 style="font-size:2.2rem">Forgot your password?</h1>
    <p class="muted">Drop your email and we’ll send a reset link.</p>
    @if($errors->any())<div class="alert alert-error mb-2"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-3">
        @csrf
        <div class="field"><label for="email">Email</label><input id="email" type="email" class="input input-lg" name="email" required></div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Send reset link</button>
    </form>
    <p class="center mt-3"><a class="link" href="{{ route('login') }}">Back to log in</a></p>
@endsection
