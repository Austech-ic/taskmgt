@extends('layouts.site')
@section('title', 'Contact')
@section('content')
<section class="section-tight">
    <div class="container split">
        <div>
            <span class="eyebrow">Say hi</span>
            <h1 style="font-size:clamp(2rem,4.5vw,3rem)">Slide into our <span class="highlight">inbox.</span></h1>
            <p class="ink-2">Hiring at scale, partnerships, press, or a payment question — we read everything and reply within one working day.</p>
            <form method="POST" action="{{ route('contact.send') }}" class="card mt-3">
                @csrf
                @if($errors->any())<div class="alert alert-error mb-2"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif
                <div class="form-grid">
                    <div class="field"><label for="name">Your name</label><input id="name" class="input" name="name" value="{{ old('name', auth()->user()?->name) }}" required></div>
                    <div class="field"><label for="email">Email</label><input id="email" type="email" class="input" name="email" value="{{ old('email', auth()->user()?->email) }}" required></div>
                    <div class="field span-2"><label for="topic">Topic</label>
                        <select id="topic" class="select" name="topic">@foreach(['general' => 'General', 'hiring' => 'Hiring & enterprise', 'talent' => 'I’m a professional', 'payments' => 'Payments', 'partnerships' => 'Partnerships', 'press' => 'Press'] as $k => $v)<option value="{{ $k }}" @selected(old('topic', request('topic')) === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="field span-2"><label for="message">Message</label><textarea id="message" class="textarea" name="message" rows="6" required minlength="10">{{ old('message') }}</textarea></div>
                    <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                </div>
                <button class="btn btn-primary btn-lg" type="submit">Send message <i data-lucide="send"></i></button>
            </form>
        </div>
        <aside class="stack sticky" style="--stack:14px">
            <div class="card card-lime"><i data-lucide="mail"></i><h3 class="mt-1">Email</h3><a href="mailto:{{ setting('contact_email') }}" class="strong">{{ setting('contact_email') }}</a></div>
            @if(setting('support_phone'))<div class="card"><i data-lucide="phone"></i><h3 class="mt-1">Phone</h3><a href="tel:{{ setting('support_phone') }}">{{ setting('support_phone') }}</a></div>@endif
            <div class="card card-soft"><h3>Quick answers</h3><p class="small muted">Most questions are answered in the <a class="link" href="{{ route('faq') }}">FAQ</a> — or ask Hail in the bottom corner.</p></div>
        </aside>
    </div>
</section>
@endsection
