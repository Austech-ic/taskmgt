@extends('layouts.site')
@section('title', 'Pricing')
@section('description', 'Free for talent. Free to search. Pay when you unlock contacts or when work gets delivered.')
@section('content')
<section class="section-tight center">
    <div class="container container-md">
        <span class="sticker">Transparent AF ✦</span>
        <h1 class="mt-3" style="font-size:clamp(2.2rem,5vw,3.6rem)">Pricing that doesn’t <span class="highlight">play games.</span></h1>
        <p class="ink-2" style="font-size:1.1rem">Talent joins free. Hirers search free. You pay to unlock contacts — and the marketplace takes {{ setting('commission_bps') / 100 }}% of eligible milestone payments. That’s it.</p>
    </div>
</section>
<section class="section-tight" style="padding-top:0">
    <div class="container grid-4">
        @foreach($plans as $plan)
            <div class="card price-card {{ $plan->is_featured ? 'featured' : '' }}">
                <div class="row-between"><h3 class="mb-0">{{ $plan->name }}</h3>@if($plan->is_featured)<span class="sticker sticker-violet" style="font-size:.72rem">Most loved</span>@endif</div>
                <p class="small muted mb-0">{{ $plan->tagline }}</p>
                <div class="price money">@if($plan->is_custom) Let’s talk @elseif($plan->price_minor === 0) ₦0 @else {{ money($plan->price_minor, $plan->currency) }}<span class="small muted" style="font-family:var(--font);font-weight:500">/{{ $plan->interval }}</span>@endif</div>
                <ul>@foreach($plan->features ?? [] as $f)<li><i data-lucide="check"></i>{{ $f }}</li>@endforeach</ul>
                <div style="margin-top:auto">
                    @if($plan->is_custom)
                        <a href="{{ route('contact', ['topic' => 'hiring']) }}" class="btn btn-block">Contact sales</a>
                    @elseif($plan->price_minor === 0)
                        <a href="{{ route('register', ['as' => 'hirer']) }}" class="btn btn-block">Start free</a>
                    @else
                        <a href="{{ auth()->check() ? route('app.billing') : route('register', ['as' => 'hirer']) }}" class="btn {{ $plan->is_featured ? 'btn-accent' : 'btn-primary' }} btn-block">Choose {{ $plan->name }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
<section class="section-tight">
    <div class="container grid-3">
        <div class="card card-ink"><div class="icon-tile lime"><i data-lucide="unlock"></i></div><h3 class="mt-2">Pay-per-contact</h3><p class="muted mb-0">No plan? Unlock a single verified professional’s contact details for {{ money(setting('unlock_price_minor')) }}. Credits never expire.</p></div>
        <div class="card"><div class="icon-tile"><i data-lucide="percent"></i></div><h3 class="mt-2">{{ setting('commission_bps') / 100 }}% marketplace fee</h3><p class="muted mb-0">Charged per milestone as money is released — never upfront on the whole contract. Shown before anyone signs.</p></div>
        <div class="card"><div class="icon-tile coral"><i data-lucide="heart-handshake"></i></div><h3 class="mt-2">Free for talent</h3><p class="muted mb-0">Profiles, portfolio and starter assessments are free. We never charge to “boost” a score or badge.</p></div>
    </div>
</section>
@if($faqs->isNotEmpty())
<section class="section-tight">
    <div class="container container-sm">
        <h2>Hiring FAQs</h2>
        @foreach($faqs as $f)<details class="faq"><summary>{{ $f->question }}</summary><p>{{ $f->answer }}</p></details>@endforeach
    </div>
</section>
@endif
@endsection
