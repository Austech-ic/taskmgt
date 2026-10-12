@extends('layouts.app')
@section('title', 'Jobs')
@section('content')
<x-page-head title="Jobs & pipeline" subtitle="Post roles, then move applicants through your stages."><a href="{{ route('app.jobs.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Post a job</a></x-page-head>
@if($jobs->isEmpty())
    <x-empty icon="briefcase" title="No jobs yet" text="Post your first role — AI can draft the description from a few notes."><a href="{{ route('app.jobs.create') }}" class="btn btn-accent">Post a job</a></x-empty>
@else
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Role</th><th>Applicants</th><th>Views</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($jobs as $job)
            <tr><td><strong>{{ $job->title }}</strong><div class="small muted">{{ $job->typeLabel() }} · {{ ucfirst($job->work_arrangement) }} · posted {{ $job->created_at->diffForHumans() }}</div></td>
                <td>{{ $job->applications_count }} @if($job->new_count)<span class="badge badge-violet">{{ $job->new_count }} new</span>@endif</td><td>{{ $job->views }}</td><td><x-status :status="$job->status" /></td>
                <td class="actions"><a class="btn btn-sm btn-primary" href="{{ route('app.jobs.pipeline', $job) }}">Pipeline</a> <a class="btn btn-sm" href="{{ route('app.jobs.edit', $job) }}">Edit</a> <a class="btn btn-sm btn-ghost" href="{{ route('jobs.show', $job) }}" target="_blank" aria-label="View public"><i data-lucide="external-link"></i></a></td></tr>
        @endforeach</tbody>
    </table></div>
    {{ $jobs->links() }}
@endif
@endsection
