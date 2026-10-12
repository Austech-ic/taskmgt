@extends('layouts.auth')
@section('title', 'Log in')
@section('art_title', 'Welcome back. Let’s get you shipping.')
@section('content')
    <h1 style="font-size:2.2rem">Welcome back 👋</h1>
    <p class="muted">Log in to your {{ setting('site_name') }} account.</p>
    @if($errors->any())<div class="alert alert-error mb-2"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif
    <form method="POST" action="{{ route('login') }}" class="mt-3">
        @csrf
        <div class="field"><label for="email">Email</label><input id="email" type="email" class="input input-lg" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
        <div class="field"><div class="row-between"><label for="password">Password</label><a class="small link" href="{{ route('password.request') }}">Forgot it?</a></div><input id="password" type="password" class="input input-lg" name="password" required autocomplete="current-password"></div>
        <label class="check mb-2"><input type="checkbox" name="remember" value="1"> Keep me logged in</label>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Log in</button>
    </form>
    <p class="center mt-3 muted">New here? <a class="link" href="{{ route('register') }}">Create a free account</a></p>
@endsection
