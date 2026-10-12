@extends('layouts.site')
@section('title', 'Tech jobs')
@section('description', 'Full-time, contract and remote tech roles from companies hiring verified talent.')
@section('content')
<section class="section-tight">
    <div class="container">
        <span class="eyebrow">Jobs board</span>
        <h1 style="font-size:clamp(2rem,4.5vw,3.2rem)">Roles that value <span class="highlight">proof.</span></h1>
        <p class="ink-2">Apply with your verified profile — no 12-page CV required.</p>
        <form class="card mt-3 row" method="GET" style="align-items:flex-end">
            <div class="grow" style="min-width:200px"><label class="label" for="q">Search</label><div class="input-group mt-1"><i data-lucide="search"></i><input id="q" class="input" name="q" value="{{ request('q') }}" placeholder="Title or keyword"></div></div>
            <div><label class="label" for="type">Type</label><select id="type" class="select mt-1" name="type"><option value="">Any</option>@foreach($types as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div><label class="label" for="arrangement">Where</label><select id="arrangement" class="select mt-1" name="arrangement"><option value="">Anywhere</option>@foreach(['remote' => 'Remote', 'hybrid' => 'Hybrid', 'onsite' => 'On-site'] as $k => $v)<option value="{{ $k }}" @selected(request('arrangement') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div><label class="label" for="seniority">Level</label><select id="seniority" class="select mt-1" name="seniority"><option value="">Any</option>@foreach(\App\Support\Labels::SENIORITY as $k => $v)<option value="{{ $k }}" @selected(request('seniority') === $k)>{{ $v }}</option>@endforeach</select></div>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</section>
<section class="section-tight" style="padding-top:0">
    <div class="container stack" style="--stack:14px">
        @forelse($jobs as $job)
            <a href="{{ route('jobs.show', $job) }}" class="card card-hover row" style="gap:18px">
                <span class="logo-tile" style="width:54px;height:54px;font-size:1.3rem">@if($job->organization->logoUrl())<img src="{{ $job->organization->logoUrl() }}" alt="">@else{{ mb_substr($job->organization->name, 0, 1) }}@endif</span>
                <div class="grow">
                    <div class="row gap-sm"><h3 class="mb-0">{{ $job->title }}</h3>@if($job->organization->isVerified())<span class="badge badge-violet"><i data-lucide="building-2"></i> Verified company</span>@endif @if($job->require_verified)<span class="badge badge-green"><i data-lucide="badge-check"></i> Verified skills required</span>@endif</div>
                    <div class="small muted mt-1">{{ $job->organization->name }} · {{ $job->typeLabel() }} · {{ ucfirst($job->work_arrangement) }} · {{ \App\Support\Labels::SENIORITY[$job->seniority] ?? '' }} @if($job->location)· {{ $job->location }}@endif</div>
                    <div class="tag-list mt-1">@foreach($job->skills->take(5) as $s)<span class="tag">{{ $s->name }}</span>@endforeach</div>
                </div>
                <div class="right hide-mobile">
                    @if($job->salaryLabel())<div class="strong money">{{ $job->salaryLabel() }}</div>@endif
                    <div class="small muted">{{ $job->created_at->diffForHumans() }}</div>
                </div>
            </a>
        @empty
            <x-empty icon="briefcase" title="No roles match right now" text="Check back soon or set up your profile so employers can find you first.">
                <a href="{{ route('register') }}" class="btn btn-accent">Create your profile</a>
            </x-empty>
        @endforelse
        {{ $jobs->links() }}
    </div>
</section>
@endsection
