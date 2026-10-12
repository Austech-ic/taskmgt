@extends('layouts.site')
@section('title', $job->title.' at '.$job->organization->name)
@section('description', $job->summary)
@section('content')
<section class="section-tight">
    <div class="container split">
        <article class="stack" style="--stack:20px">
            <a href="{{ route('jobs.index') }}" class="small link">← All jobs</a>
            <div class="row" style="gap:18px">
                <span class="logo-tile" style="width:64px;height:64px;font-size:1.5rem">@if($job->organization->logoUrl())<img src="{{ $job->organization->logoUrl() }}" alt="">@else{{ mb_substr($job->organization->name, 0, 1) }}@endif</span>
                <div><h1 class="mb-0" style="font-size:clamp(1.8rem,4vw,2.8rem)">{{ $job->title }}</h1><div class="muted strong">{{ $job->organization->name }} @if($job->organization->isVerified())<span class="badge badge-violet">Verified company</span>@endif</div></div>
            </div>
            <div class="chips">
                <span class="chip"><i data-lucide="briefcase"></i>{{ $job->typeLabel() }}</span>
                <span class="chip"><i data-lucide="laptop"></i>{{ ucfirst($job->work_arrangement) }}</span>
                <span class="chip"><i data-lucide="signal"></i>{{ \App\Support\Labels::SENIORITY[$job->seniority] ?? '' }}</span>
                @if($job->location)<span class="chip"><i data-lucide="map-pin"></i>{{ $job->location }}</span>@endif
                @if($job->salaryLabel())<span class="chip chip-verified"><i data-lucide="banknote"></i>{{ $job->salaryLabel() }}</span>@endif
            </div>
            @if($job->summary)<p class="ink-2" style="font-size:1.12rem">{{ $job->summary }}</p>@endif
            <div class="card prose">
                {!! nl2br(e($job->description)) !!}
                @if($job->responsibilities)<h3>What you’ll do</h3><ul>@foreach(preg_split('/\r?\n/', trim($job->responsibilities)) as $l)@if(trim($l))<li>{{ ltrim(trim($l), '-•* ') }}</li>@endif @endforeach</ul>@endif
                @if($job->benefits)<h3>What you’ll get</h3><ul>@foreach(preg_split('/\r?\n/', trim($job->benefits)) as $l)@if(trim($l))<li>{{ ltrim(trim($l), '-•* ') }}</li>@endif @endforeach</ul>@endif
            </div>
            <div class="card">
                <h3>Skills</h3>
                <div class="chips">@foreach($job->skills as $s)<span class="chip {{ $s->pivot->is_required ? 'chip-verified' : '' }}">{{ $s->name }} · {{ $s->pivot->is_required ? 'required' : 'nice to have' }}</span>@endforeach</div>
            </div>
            @if($job->organization->about)
                <div class="card card-soft"><h3>About {{ $job->organization->name }}</h3><p class="mb-0 ink-2">{{ $job->organization->about }}</p></div>
            @endif
        </article>
        <aside class="sticky">
            <div class="card card-pop">
                @if($job->deadline)<div class="small muted">Apply by</div><div class="strong mb-2">{{ $job->deadline->format('j M Y') }}</div>@endif
                @auth
                    @if($application)
                        <div class="alert alert-success"><i data-lucide="check-circle-2"></i><div>You applied {{ $application->created_at->diffForHumans() }}.<br>Stage: <strong>{{ $application->stageLabel() }}</strong></div></div>
                        <a href="{{ route('app.applications.index') }}" class="btn btn-block mt-2">Track application</a>
                    @elseif(auth()->user()->profile)
                        @if($missing->isNotEmpty())
                            <div class="alert alert-warn mb-2"><i data-lucide="alert-triangle"></i><div>Not yet verified for: {{ $missing->pluck('name')->implode(', ') }}. @if($job->require_verified)This role requires verified skills.@else You can still apply.@endif</div></div>
                        @endif
                        @if(! ($job->require_verified && $missing->isNotEmpty()))
                            <form method="POST" action="{{ route('app.jobs.apply', $job) }}">
                                @csrf
                                <div class="field"><label for="cover">Why you? <span class="muted">(optional)</span></label><textarea id="cover" class="textarea" name="cover_letter" rows="5" maxlength="3000" placeholder="2–3 lines on why this role + what you’d ship first."></textarea></div>
                                <div class="field"><label for="exp">Expected monthly salary (₦) <span class="muted">(optional)</span></label><input id="exp" class="input" name="expected_salary" inputmode="decimal"></div>
                                <button class="btn btn-accent btn-block btn-lg" type="submit">Apply with my profile</button>
                            </form>
                        @else
                            <a href="{{ route('app.assessments.index') }}" class="btn btn-accent btn-block">Get verified first</a>
                        @endif
                    @else
                        <a href="{{ route('app.onboarding', ['want' => 'talent']) }}" class="btn btn-accent btn-block">Create a talent profile to apply</a>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="btn btn-accent btn-block btn-lg">Join free to apply</a>
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-block mt-1">I have an account</a>
                @endauth
                <div class="divider"></div>
                <div class="small muted">{{ $job->openings }} {{ \Illuminate\Support\Str::plural('opening', $job->openings) }} · posted {{ $job->created_at->diffForHumans() }}</div>
            </div>
        </aside>
    </div>
</section>
@endsection
