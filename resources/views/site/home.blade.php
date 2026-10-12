@extends('layouts.site')

@section('content')
{{-- ============ HERO ============ --}}
<section class="hero">
    <div class="blob blob-violet"></div>
    <div class="blob blob-lime"></div>
    <div class="container hero-grid">
        <div>
            <span class="sticker mb-3">✦ {{ setting('hero_eyebrow') }}</span>
            <h1 class="mt-3">{!! preg_replace('/(ship\.?|actually)/i', '<span class="highlight">$1</span>', e(setting('hero_title')), 1) !!}</h1>
            <p class="lede mt-2">{{ setting('hero_subtitle') }}</p>

            <div class="row mt-3 gap-sm">
                <a href="{{ route('talent.index') }}" class="btn btn-primary btn-lg"><i data-lucide="search"></i> Find verified talent</a>
                <a href="{{ route('register') }}" class="btn btn-accent btn-lg">Get verified — it’s free <i data-lucide="arrow-up-right"></i></a>
            </div>

            <form method="POST" action="{{ route('talent.ai') }}" class="mt-3" style="max-width:560px">
                @csrf
                <label for="ai-q" class="small muted row gap-xs mb-1"><span class="sparkle"><i data-lucide="sparkles"></i></span> Or just describe who you need</label>
                <div class="input-group">
                    <i data-lucide="wand-sparkles"></i>
                    <input id="ai-q" name="prompt" class="input input-lg" placeholder="“Senior React dev in Lagos, open to remote, verified”" maxlength="400" required>
                </div>
            </form>

            <div class="trust-row mt-3">
                <span><i data-lucide="badge-check"></i> Skills tested, not claimed</span>
                <span><i data-lucide="shield-check"></i> Milestone payments</span>
                <span><i data-lucide="eye-off"></i> Private contact details</span>
            </div>
        </div>

        <div class="hero-stack" aria-hidden="true">
            @php($hero = $featured->first())
            <div class="float-card one">
                <div class="row">
                    <img class="avatar avatar-lg" src="{{ $hero?->user->avatarUrl() ?? \App\Models\User::initialsAvatar('Adaeze Okafor') }}" alt="">
                    <div class="grow">
                        <strong style="font-family:var(--display);font-size:1.15rem">{{ $hero?->user->name ?? 'Adaeze O.' }} <span class="verified-tick"><i data-lucide="check"></i></span></strong>
                        <div class="small muted">{{ $hero?->primary_role ?? 'Frontend Engineer' }} · {{ $hero?->city ?? 'Lagos' }}</div>
                    </div>
                    <span class="badge badge-green"><span class="dot"></span> Open to work</span>
                </div>
                <div class="mt-2" style="display:grid;gap:10px">
                    @foreach(($hero ? $hero->verifiedSkills()->take(3) : collect()) as $s)
                        <div class="row-between"><span class="strong small">{{ $s->name }}</span><x-level :level="$s->pivot->verified_level" /></div>
                    @endforeach
                    @if(! $hero)
                        <div class="row-between"><span class="strong small">React</span><x-level :level="4" /></div>
                        <div class="row-between"><span class="strong small">TypeScript</span><x-level :level="3" /></div>
                    @endif
                </div>
            </div>
            <div class="float-card two">
                <div class="small strong" style="opacity:.75">MILESTONE 2 OF 3</div>
                <div style="font:800 1.6rem/1.1 var(--display);letter-spacing:-.03em" class="mt-1">₦450k released 🎉</div>
                <div class="small mt-1">Approved by client · paid to earnings</div>
            </div>
            <div class="float-card three">
                <div class="small" style="opacity:.8">Assessment</div>
                <div style="font:800 1.3rem/1.1 var(--display)">Passed · L4</div>
                <div class="meter lime mt-2"><i style="width:90%"></i></div>
            </div>
        </div>
    </div>
</section>

{{-- ============ MARQUEE ============ --}}
<div class="marquee" aria-hidden="true">
    <div class="marquee-track">
        @for($i = 0; $i < 2; $i++)
            <span>@foreach($skills as $s)<span>{{ $s }}</span>@endforeach</span>
        @endfor
    </div>
</div>

{{-- ============ STATS ============ --}}
<section class="section-tight">
    <div class="container grid-4">
        <div><div class="stat-big">{{ number_format($stats['talent']) }}</div><div class="muted">approved professionals</div></div>
        <div><div class="stat-big">{{ number_format($stats['verified']) }}</div><div class="muted">assessment-verified skills</div></div>
        <div><div class="stat-big">{{ $stats['assessments'] }}</div><div class="muted">live skill assessments</div></div>
        <div><div class="stat-big">{{ $stats['jobs'] }}</div><div class="muted">open roles right now</div></div>
    </div>
</section>

{{-- ============ BENTO: WHY ============ --}}
<section class="section" style="padding-top:24px">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Why {{ setting('site_name') }}</span>
            <h2>One platform. Proof, hiring and payment — <span class="scribble">sorted.</span></h2>
            <p>We’re not another CV dump. Every profile answers four questions: what can this person do, did we test it, what’s the evidence, and how do I hire them safely?</p>
        </div>
        <div class="bento">
            <div class="card card-pop wide">
                <div class="icon-tile ink"><i data-lucide="badge-check"></i></div>
                <h3>Verified skills, not vibes</h3>
                <p class="muted mb-0">Modular, skill-by-skill assessments with randomised questions, server-side scoring and human review for practical work. Badges show level, score band and date — and expire, so they stay honest.</p>
            </div>
            <div class="card card-lime">
                <div class="icon-tile ink"><i data-lucide="search-check"></i></div>
                <h3>Search that respects evidence</h3>
                <p class="mb-0">Filter by verified level, experience and availability. Ranking is never for sale.</p>
            </div>
            <div class="card">
                <div class="icon-tile coral"><i data-lucide="lock-keyhole"></i></div>
                <h3>Privacy by default</h3>
                <p class="muted mb-0">Phone and email stay hidden until an authorised unlock — and talent can opt out entirely.</p>
            </div>
            <div class="card card-violet">
                <div class="icon-tile" style="background:rgba(255,255,255,.18);color:#fff"><i data-lucide="wallet-cards"></i></div>
                <h3>Milestone payments</h3>
                <p class="muted mb-0">Fund one milestone at a time via licensed payment partners. Release on approval. Disputes pause the money.</p>
            </div>
            <div class="card">
                <div class="icon-tile sky"><i data-lucide="sparkles"></i></div>
                <h3>AI that does the boring bits</h3>
                <p class="muted mb-0">Bio glow-ups, job posts, project scopes and plain-English search. Humans still make every call that matters.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ HOW IT WORKS (tabs) ============ --}}
<section class="section" style="background:var(--paper-2)" x-data="{ tab: 'talent' }">
    <div class="container">
        <div class="row-between mb-4">
            <div class="section-head mb-0">
                <span class="eyebrow">How it works</span>
                <h2>From sign-up to paid in four moves.</h2>
            </div>
            <div class="tabs-pill" role="tablist">
                <button role="tab" :class="{ 'is-active': tab === 'talent' }" @click="tab = 'talent'">I’m talent</button>
                <button role="tab" :class="{ 'is-active': tab === 'hirer' }" @click="tab = 'hirer'">I’m hiring</button>
            </div>
        </div>
        <div class="steps" x-show="tab === 'talent'">
            <div class="step"><h3>Build your profile</h3><p class="muted">Claim skills from our taxonomy, add projects and let AI polish your bio.</p></div>
            <div class="step"><h3>Prove it</h3><p class="muted">Take bite-sized assessments. Pass → verified badge with your level.</p></div>
            <div class="step"><h3>Get discovered</h3><p class="muted">Employers find you by verified skill. Apply to jobs or pitch for projects.</p></div>
            <div class="step"><h3>Ship & get paid</h3><p class="muted">Deliver milestones, get approved, get paid. Reviews stack your rep.</p></div>
        </div>
        <div class="steps" x-show="tab === 'hirer'" x-cloak>
            <div class="step"><h3>Search or describe</h3><p class="muted">Filter by verified level — or type what you need in plain English.</p></div>
            <div class="step"><h3>Shortlist</h3><p class="muted">Compare candidates, leave notes, share lists with your team.</p></div>
            <div class="step"><h3>Connect</h3><p class="muted">Unlock contacts with your plan, message on-platform or post a job.</p></div>
            <div class="step"><h3>Hire or contract</h3><p class="muted">Run your pipeline, or fund milestones for a project — safely.</p></div>
        </div>
    </div>
</section>

{{-- ============ FEATURED TALENT ============ --}}
@if($featured->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="row-between mb-4">
            <div class="section-head mb-0">
                <span class="eyebrow">Fresh off the verification line</span>
                <h2>Talent with receipts.</h2>
            </div>
            <a href="{{ route('talent.index') }}" class="btn">Browse everyone <i data-lucide="arrow-right"></i></a>
        </div>
        <div class="grid-3">
            @foreach($featured as $profile)
                @include('partials.talent-card', ['profile' => $profile])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ JOBS + PROJECTS ============ --}}
<section class="section" style="padding-top:0">
    <div class="container grid-2">
        <div class="card">
            <div class="card-head"><h3><i data-lucide="briefcase" style="display:inline;width:20px;vertical-align:-3px"></i> Latest jobs</h3><a class="link small" href="{{ route('jobs.index') }}">All jobs →</a></div>
            <div class="list-divided">
                @forelse($jobs as $job)
                    <a href="{{ route('jobs.show', $job) }}" class="row" style="gap:14px">
                        <span class="logo-tile">{{ mb_substr($job->organization->name, 0, 1) }}</span>
                        <span class="grow"><strong class="truncate" style="display:block">{{ $job->title }}</strong><span class="small muted">{{ $job->organization->name }} · {{ $job->typeLabel() }} · {{ ucfirst($job->work_arrangement) }}</span></span>
                        @if($job->salaryLabel())<span class="small strong money">{{ $job->salaryLabel() }}</span>@endif
                    </a>
                @empty
                    <p class="muted">New roles land here first.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h3><i data-lucide="folder-kanban" style="display:inline;width:20px;vertical-align:-3px"></i> Open projects</h3><a class="link small" href="{{ route('projects.index') }}">All projects →</a></div>
            <div class="list-divided">
                @forelse($projects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="row" style="gap:14px">
                        <span class="icon-tile sm lime"><i data-lucide="rocket"></i></span>
                        <span class="grow"><strong class="truncate" style="display:block">{{ $project->title }}</strong><span class="small muted">{{ ucfirst($project->contract_type) }} price · posted {{ $project->created_at->diffForHumans() }}</span></span>
                        <span class="small strong money">{{ $project->budgetLabel() }}</span>
                    </a>
                @empty
                    <p class="muted">Projects from clients show up here.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>

{{-- ============ TESTIMONIALS ============ --}}
@if($testimonials->isNotEmpty())
<section class="section" style="background:var(--paper-2)">
    <div class="container">
        <div class="section-head center center"><span class="eyebrow">The vibe check</span><h2>Built for both sides of the table.</h2></div>
        <div class="grid-3">
            @foreach($testimonials as $i => $t)
                <figure class="card {{ $i === 1 ? 'card-pop' : '' }}" style="margin:0">
                    <p class="quote">“{{ $t->quote }}”</p>
                    <figcaption class="row mt-2"><img class="avatar" src="{{ \App\Models\User::initialsAvatar($t->name) }}" alt=""><div><strong>{{ $t->name }}</strong><div class="small muted">{{ $t->role }}</div></div></figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ BLOG ============ --}}
@if($posts->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="row-between mb-4"><div class="section-head mb-0"><span class="eyebrow">From the blog</span><h2>Career tips, hiring hacks, product drops.</h2></div><a href="{{ route('blog.index') }}" class="btn">Read the blog</a></div>
        <div class="grid-3">
            @foreach($posts as $post)
                <a href="{{ route('blog.show', $post) }}" class="card card-hover" style="display:flex;flex-direction:column;gap:10px">
                    <div style="font-size:2.4rem">{{ $post->emoji ?: '📝' }}</div>
                    <span class="badge badge-violet" style="align-self:flex-start">{{ $post->category }}</span>
                    <h3 class="mb-0">{{ $post->title }}</h3>
                    <p class="muted small clamp-3 mb-0">{{ $post->excerpt }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ CTA ============ --}}
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="cta-band">
            <div class="blob blob-lime"></div>
            <span class="sticker sticker-violet">No cap 🧢</span>
            <h2 class="mt-2">Your next hire — or your next gig — is one assessment away.</h2>
            <p class="muted" style="max-width:52ch">Free for talent. Free to search. Pay only when you unlock or when work gets delivered.</p>
            <div class="row mt-3">
                <a href="{{ route('register') }}" class="btn btn-accent btn-lg">Create free account</a>
                <a href="{{ route('pricing') }}" class="btn btn-lg" style="--b-bg:transparent;--b-fg:var(--paper);--b-bd:color-mix(in srgb,var(--paper) 35%,transparent)">See pricing</a>
            </div>
        </div>
    </div>
</section>
@endsection
