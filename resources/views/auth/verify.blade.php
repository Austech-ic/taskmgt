@extends('layouts.auth')
@section('title', 'Verify your email')
@section('art_title', 'One quick check and you’re in.')
@section('content')
    <div class="icon-tile lime mb-2"><i data-lucide="mail-check"></i></div>
    <h1 style="font-size:2.2rem">Check your inbox 📬</h1>
    <p class="muted">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Tap it to unlock your dashboard.</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mt-3">@csrf<button class="btn btn-primary btn-block" type="submit">Resend link</button></form>
    <form method="POST" action="{{ route('logout') }}" class="mt-1">@csrf<button class="btn btn-ghost btn-block" type="submit">Use a different account</button></form>
@endsection
