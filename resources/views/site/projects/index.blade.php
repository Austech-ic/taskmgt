@extends('layouts.site')
@section('title', 'Freelance tech projects')
@section('description', 'Milestone-based tech projects from clients who pay on delivery.')
@section('content')
<section class="section-tight">
    <div class="container">
        <span class="eyebrow">Project marketplace</span>
        <h1 style="font-size:clamp(2rem,4.5vw,3.2rem)">Real briefs. <span class="highlight">Milestone pay.</span></h1>
        <p class="ink-2" style="max-width:60ch">Clients fund each milestone through licensed payment partners before you start it. Ship, get approved, get paid.</p>
        <form class="card mt-3 row" method="GET" style="align-items:flex-end">
            <div class="grow" style="min-width:220px"><label class="label" for="q">Search projects</label><div class="input-group mt-1"><i data-lucide="search"></i><input id="q" class="input" name="q" value="{{ request('q') }}" placeholder="e.g. booking website, API, security review"></div></div>
            <div><label class="label" for="type">Pricing</label><select id="type" class="select mt-1" name="type"><option value="">Any</option><option value="fixed" @selected(request('type') === 'fixed')>Fixed price</option><option value="hourly" @selected(request('type') === 'hourly')>Hourly</option></select></div>
            <button class="btn btn-primary" type="submit">Search</button>
            @auth @if(auth()->user()->is_hirer)<a href="{{ route('app.projects.create') }}" class="btn btn-accent"><i data-lucide="plus"></i> Post a project</a>@endif @endauth
        </form>
    </div>
</section>
<section class="section-tight" style="padding-top:0">
    <div class="container grid-2">
        @forelse($projects as $project)
            <a href="{{ route('projects.show', $project) }}" class="card card-hover" style="display:flex;flex-direction:column;gap:10px">
                <div class="row-between"><span class="badge badge-violet">{{ ucfirst($project->contract_type) }} price</span><span class="small muted">{{ $project->created_at->diffForHumans() }}</span></div>
                <h3 class="mb-0">{{ $project->title }}</h3>
                <p class="small muted clamp-3 mb-0">{{ $project->description }}</p>
                <div class="tag-list">@foreach($project->skills as $s)<span class="tag">{{ $s->name }}</span>@endforeach</div>
                <div class="row-between" style="margin-top:auto;padding-top:10px;border-top:1px dashed var(--line-2)">
                    <strong class="money">{{ $project->budgetLabel() }}</strong>
                    <span class="small muted"><i data-lucide="users" style="display:inline;width:14px;vertical-align:-2px"></i> {{ $project->proposals_count }} proposals</span>
                </div>
            </a>
        @empty
            <div style="grid-column:1/-1"><x-empty icon="folder-search" title="No open projects right now" text="New briefs land here first. Clients — your project could be the one.">
                <a href="{{ route('register', ['as' => 'hirer']) }}" class="btn btn-accent">Post a project</a></x-empty></div>
        @endforelse
    </div>
    <div class="container">{{ $projects->links() }}</div>
</section>
@endsection
