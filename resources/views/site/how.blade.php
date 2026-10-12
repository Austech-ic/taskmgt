@extends('layouts.site')
@section('title', 'How it works')
@section('content')
<section class="hero" style="padding-bottom:24px">
    <div class="blob blob-violet"></div>
    <div class="container container-md center">
        <span class="sticker sticker-sky">The playbook 📖</span>
        <h1 class="mt-3" style="margin-inline:auto;max-width:16ch">How {{ setting('site_name') }} works.</h1>
        <p class="lede" style="margin-inline:auto">Proof first, then hiring, then payment — with humans in the loop wherever trust matters.</p>
    </div>
</section>
<section class="section-tight">
    <div class="container stack" style="--stack:28px">
        @foreach([
            ['user-round-plus', 'Create a profile', 'Pick skills from a controlled taxonomy, add work history, projects and links. Your phone and private email are never public.', 'violet'],
            ['badge-check', 'Pass assessments', 'Each skill has its own assessment: scenario questions, debugging, and for hands-on roles a practical brief reviewed by an expert. Pass → a verified badge with level and date.', 'lime'],
            ['user-check', 'Get approved', 'Our team reviews profiles for completeness and authenticity. Pass your primary skill and you can go live conditionally while the rest is reviewed.', 'sky'],
            ['search', 'Get discovered', 'Employers search by verified level, experience and availability. Ranking reflects evidence — paid plans never buy a better position.', 'coral'],
            ['handshake', 'Hire or contract', 'Employers unlock contacts or run their pipeline on Devhail. Clients post projects, compare proposals and sign a contract with milestones and acceptance criteria.', 'violet'],
            ['wallet', 'Fund → deliver → release', 'Clients fund each milestone through a licensed payment partner. Professionals deliver, clients approve or request revisions, and approved milestones are released minus commission.', 'lime'],
            ['star', 'Build reputation', 'After verified contracts, both sides leave multi-dimension reviews: quality, timeliness, communication, professionalism. Disputes go to a neutral officer.', 'sky'],
        ] as $i => [$icon, $title, $text, $tone])
            <div class="card row row-top" style="gap:22px">
                <div class="icon-tile {{ $tone }}" style="width:58px;height:58px"><i data-lucide="{{ $icon }}"></i></div>
                <div class="grow"><div class="small muted strong">STEP {{ $i + 1 }}</div><h3>{{ $title }}</h3><p class="muted mb-0">{{ $text }}</p></div>
            </div>
        @endforeach
        <div class="cta-band center"><h2 style="margin-inline:auto">Ready when you are.</h2><div class="row mt-2" style="justify-content:center"><a class="btn btn-accent btn-lg" href="{{ route('register') }}">Join as talent</a><a class="btn btn-lg" style="--b-bg:transparent;--b-fg:var(--paper);--b-bd:rgba(255,255,255,.3)" href="{{ route('register', ['as' => 'hirer']) }}">Start hiring</a></div></div>
    </div>
</section>
@endsection
