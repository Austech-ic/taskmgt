@extends('layouts.base')
@section('title', '404')
@section('body')
<div class="install-shell">
    <div class="card card-pop center" style="max-width:520px">
        <div style="font:800 5rem/1 var(--display);letter-spacing:-.06em">404</div>
        <h1 style="font-size:1.8rem" class="mt-2">This page ghosted us 👻</h1>
        <p class="muted">{{ $exception?->getMessage() && 404 === 403 ? $exception->getMessage() : 'We looked everywhere. It’s either moved, private, or never existed.' }}</p>
        <div class="row" style="justify-content:center"><a href="{{ url('/') }}" class="btn btn-primary">Take me home</a><button class="btn" onclick="history.back()">Go back</button></div>
    </div>
</div>
@endsection
