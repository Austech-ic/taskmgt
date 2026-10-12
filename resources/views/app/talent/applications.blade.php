@extends('layouts.app')
@section('title', 'Applications')
@section('content')
<x-page-head title="Applications" subtitle="Where every job you’ve applied to stands. Employers’ internal notes stay private."><a href="{{ route('jobs.index') }}" class="btn btn-primary"><i data-lucide="search"></i> Find jobs</a></x-page-head>
@if($applications->isEmpty())
    <x-empty icon="send" title="No applications yet" text="Verified profiles get noticed faster. Find a role that fits and apply in one tap."><a href="{{ route('jobs.index') }}" class="btn btn-accent">Browse jobs</a></x-empty>
@else
    <div class="stack" style="--stack:14px">
        @foreach($applications as $app)
            @php($stages = array_keys(\App\Models\Application::STAGES))
            @php($idx = array_search($app->stage, $stages))
            <div class="card">
                <div class="row-between">
                    <div class="row"><span class="logo-tile">{{ mb_substr($app->job->organization->name, 0, 1) }}</span><div><a href="{{ route('jobs.show', $app->job) }}" class="strong">{{ $app->job->title }}</a><div class="small muted">{{ $app->job->organization->name }} · applied {{ $app->created_at->diffForHumans() }}</div></div></div>
                    <div class="row"><x-status :status="$app->withdrawn_at ? 'closed' : $app->stage" :label="$app->stageLabel()" />
                        @if(! $app->withdrawn_at && ! in_array($app->stage, ['hired', 'closed']))<form method="POST" action="{{ route('app.applications.withdraw', $app) }}" data-confirm="Withdraw this application?">@csrf<button class="btn btn-sm btn-ghost">Withdraw</button></form>@endif</div>
                </div>
                @if(! $app->withdrawn_at && $app->stage !== 'closed')
                    <div class="row mt-2 gap-xs" aria-label="Progress">
                        @foreach(array_slice($stages, 0, 6) as $i => $s)
                            <div class="grow"><div class="meter {{ $i <= $idx ? 'green' : '' }}"><i style="width:{{ $i <= $idx ? 100 : 0 }}%"></i></div><div class="small muted mt-1 hide-mobile">{{ \App\Models\Application::STAGES[$s] }}</div></div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    {{ $applications->links() }}
@endif
@endsection
