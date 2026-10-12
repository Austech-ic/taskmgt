@extends('layouts.app')
@section('title', $job->title.' · Pipeline')
@section('content')
<a href="{{ route('app.jobs.index') }}" class="small link">← Jobs</a>
<x-page-head :title="$job->title" subtitle="Move candidates through stages. Candidates are notified of key changes; your notes stay internal.">
    <a class="btn" href="{{ route('app.jobs.edit', $job) }}"><i data-lucide="pencil"></i> Edit</a><a class="btn" href="{{ route('jobs.show', $job) }}" target="_blank"><i data-lucide="external-link"></i> Public page</a>
</x-page-head>
@if($withdrawn)<p class="small muted">{{ $withdrawn }} withdrawn {{ \Illuminate\Support\Str::plural('application', $withdrawn) }} hidden.</p>@endif
<div class="kanban">
    @foreach($columns as $stage => $col)
        <div class="kanban-col">
            <h4>{{ $col['label'] }} <span class="badge">{{ $col['items']->count() }}</span></h4>
            @foreach($col['items'] as $app)
                @php($p = $app->profile)
                <div class="kanban-card" x-data="{ open: false }">
                    <div class="row"><img class="avatar avatar-sm" src="{{ $p->user->avatarUrl() }}" alt=""><a class="grow strong small" href="{{ route('talent.show', $p) }}" target="_blank">{{ $p->user->name }}</a>@if($app->rating)<span class="small">{{ str_repeat('★', $app->rating) }}</span>@endif</div>
                    <div class="small muted mt-1">{{ $p->primary_role }} · {{ $p->years_experience }}y</div>
                    <div class="tag-list mt-1">@foreach($job->skills as $s)@php($have = $p->skills->firstWhere('id', $s->id))@if($have)<span class="tag" style="{{ $have->pivot->verified_level ? 'background:var(--green-soft);color:var(--green)' : '' }}">{{ $s->name }}{{ $have->pivot->verified_level ? ' L'.$have->pivot->verified_level : '' }}</span>@endif @endforeach</div>
                    @if($app->expected_salary)<div class="small mt-1">Expects <strong class="money">{{ money($app->expected_salary, $job->currency, false) }}</strong>/mo</div>@endif
                    <button class="btn btn-sm btn-block mt-2" @click="open = !open">Details & move</button>
                    <div x-show="open" x-cloak class="mt-2">
                        @if($app->cover_letter)<p class="small" style="white-space:pre-wrap">{{ $app->cover_letter }}</p>@endif
                        @if($app->cv_path)<a class="small link" href="{{ route('app.applications.cv', $app) }}">Download CV</a>@endif
                        @if($app->internal_notes)<p class="small muted" style="white-space:pre-wrap">{{ $app->internal_notes }}</p>@endif
                        <form method="POST" action="{{ route('app.applications.stage', $app) }}" class="mt-1">
                            @csrf @method('PUT')
                            <select class="select" name="stage">@foreach(\App\Models\Application::STAGES as $k => $v)<option value="{{ $k }}" @selected($app->stage === $k)>{{ $v }}</option>@endforeach</select>
                            <select class="select mt-1" name="rating"><option value="">Rating</option>@for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected($app->rating === $i)>{{ str_repeat('★', $i) }}</option>@endfor</select>
                            <textarea class="textarea mt-1" name="note" rows="2" placeholder="Internal note"></textarea>
                            <button class="btn btn-primary btn-sm btn-block mt-1">Update</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endsection
