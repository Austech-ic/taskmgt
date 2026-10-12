@extends('layouts.site')
@section('title', 'Skill assessments')
@section('description', 'Modular, practical assessments that turn your skills into verified badges.')
@section('content')
<section class="section-tight">
    <div class="container">
        <span class="eyebrow">Assessments</span>
        <h1 style="font-size:clamp(2rem,4.5vw,3.2rem)">Earn the badge. <span class="highlight">Keep the receipts.</span></h1>
        <p class="ink-2" style="max-width:62ch">Short, skill-specific tests — not one mega-exam. Questions are randomised, scored on our servers and reviewed by humans for practical work. Pass and your level goes live on your profile.</p>
        <div class="grid-auto mt-4" style="grid-template-columns:repeat(auto-fill,minmax(min(100%,200px),1fr))">
            @foreach($levels as $n => $l)
                <div class="card {{ $n === 3 ? 'card-lime' : '' }}"><x-level :level="$n" /><p class="small mt-1 mb-0">{{ $l['blurb'] }}</p></div>
            @endforeach
        </div>
    </div>
</section>
<section class="section-tight" style="padding-top:0">
    <div class="container stack" style="--stack:36px">
        @foreach($assessments as $category => $items)
            <div>
                <h2 style="font-size:1.5rem">{{ $category }}</h2>
                <div class="grid-3">
                    @foreach($items as $a)
                        <div class="card card-hover" style="display:flex;flex-direction:column;gap:10px">
                            <div class="row-between"><span class="icon-tile sm"><i data-lucide="badge-check"></i></span>@if($a->requires_review)<span class="badge badge-amber">+ practical task</span>@else<span class="badge">Knowledge + scenarios</span>@endif</div>
                            <h3 class="mb-0">{{ $a->title }}</h3>
                            <p class="small muted mb-0">{{ $a->description }}</p>
                            <div class="row small muted gap-lg" style="margin-top:auto"><span>⏱ {{ $a->duration_minutes }} min</span><span>❓ {{ $a->question_count }} Qs</span><span>🎯 {{ $a->pass_mark }}% to pass</span></div>
                            @auth
                                <a href="{{ auth()->user()->profile ? route('app.assessments.show', $a) : route('app.onboarding', ['want' => 'talent']) }}" class="btn btn-primary btn-sm">Take it</a>
                            @else
                                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Join free to take it</a>
                            @endauth
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="card card-soft">
            <h3>On the roadmap</h3>
            <p class="muted">Our taxonomy covers {{ $categories->sum(fn ($c) => $c->skills->count()) }} skills across {{ $categories->count() }} categories. New assessments roll out as our subject-matter reviewers sign them off.</p>
            <div class="chips">@foreach($categories as $c)<span class="chip">{{ $c->name }} · {{ $c->skills->count() }}</span>@endforeach</div>
        </div>
    </div>
</section>
@endsection
