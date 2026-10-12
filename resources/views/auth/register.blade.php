@extends('layouts.auth')
@section('title', 'Join')
@section('art_sticker', $as === 'hirer' ? 'Hire with receipts' : 'Skills > vibes')
@section('art_title', $as === 'hirer' ? 'Stop guessing. Start hiring proven talent.' : 'Your skills deserve receipts.')
@section('content')
    <h1 style="font-size:2.2rem">Join {{ setting('site_name') }} ✦</h1>
    <p class="muted">Free forever for talent. Free to search for hirers. Takes about 60 seconds.</p>
    @if($errors->any())<div class="alert alert-error mb-2"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif
    <form method="POST" action="{{ route('register') }}" class="mt-3" x-data="{ as: '{{ old('as', $as) }}' }">
        @csrf
        <div class="radio-cards mb-2">
            <label class="radio-card"><input type="radio" name="as" value="talent" x-model="as"><div class="card"><div class="icon-tile sm lime mb-1"><i data-lucide="sparkles"></i></div><strong>I’m talent</strong><div class="small muted">Get verified & hired</div></div></label>
            <label class="radio-card"><input type="radio" name="as" value="hirer" x-model="as"><div class="card"><div class="icon-tile sm mb-1"><i data-lucide="building-2"></i></div><strong>I’m hiring</strong><div class="small muted">Find & pay talent</div></div></label>
        </div>
        <div class="field"><label for="name">Full name</label><input id="name" class="input" name="name" value="{{ old('name') }}" required autocomplete="name"></div>
        <div class="field"><label for="email">Email</label><input id="email" type="email" class="input" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
        <div class="form-grid">
            <div class="field"><label for="password">Password</label><input id="password" type="password" class="input" name="password" required minlength="8" autocomplete="new-password"></div>
            <div class="field"><label for="password_confirmation">Confirm</label><input id="password_confirmation" type="password" class="input" name="password_confirmation" required autocomplete="new-password"></div>
        </div>
        <span class="hint">8+ characters with letters and numbers.</span>
        <label class="check mt-2 mb-2"><input type="checkbox" name="terms" value="1" required> <span>I agree to the <a class="link" href="{{ route('pages.show', 'terms') }}" target="_blank">terms</a> and <a class="link" href="{{ route('pages.show', 'privacy') }}" target="_blank">privacy policy</a>, including the assessment and conduct rules.</span></label>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Create my account</button>
    </form>
    <p class="center mt-3 muted">Already in? <a class="link" href="{{ route('login') }}">Log in</a></p>
@endsection
