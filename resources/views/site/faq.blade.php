@extends('layouts.site')
@section('title', 'FAQ')
@section('content')
<section class="section-tight">
    <div class="container container-sm">
        <span class="eyebrow">Help centre</span>
        <h1 style="font-size:clamp(2rem,4.5vw,3rem)">Questions? <span class="highlight">Answered.</span></h1>
        <p class="ink-2">Still stuck? Tap the chat bubble to ask Hail, or <a class="link" href="{{ route('contact') }}">talk to a human</a>.</p>
        @foreach(['everyone' => 'The basics', 'talent' => 'For talent', 'hirers' => 'For employers & clients'] as $key => $label)
            @if(isset($faqs[$key]))
                <h2 class="mt-4" style="font-size:1.4rem">{{ $label }}</h2>
                @foreach($faqs[$key] as $f)<details class="faq"><summary>{{ $f->question }}</summary><p>{{ $f->answer }}</p></details>@endforeach
            @endif
        @endforeach
    </div>
</section>
@endsection
