@extends('layouts.admin')
@section('title', 'Jobs & projects')
@section('content')
<x-page-head title="Jobs & projects" subtitle="Moderate listings: close spam, misleading pay or anything off-policy." />
<div class="row-between mb-3"><div class="tabs-pill"><a href="{{ route('admin.listings.index', ['tab' => 'jobs']) }}" @class(['is-active' => $tab === 'jobs'])>Jobs</a><a href="{{ route('admin.listings.index', ['tab' => 'projects']) }}" @class(['is-active' => $tab === 'projects'])>Projects</a></div>
    <form><input type="hidden" name="tab" value="{{ $tab }}"><input class="input" name="q" value="{{ request('q') }}" placeholder="Search title"></form></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Title</th><th>By</th><th>{{ $tab === 'jobs' ? 'Applicants' : 'Proposals' }}</th><th>Status</th><th>Moderate</th></tr></thead><tbody>
@foreach(($tab === 'jobs' ? $jobs : $projects) as $l)
    <tr><td><a class="strong" href="{{ $tab === 'jobs' ? route('jobs.show', $l) : route('projects.show', $l) }}" target="_blank">{{ $l->title }}</a><div class="small muted">{{ $l->created_at->format('j M Y') }}</div></td>
        <td class="small">{{ $tab === 'jobs' ? $l->organization->name : $l->client->name }}</td><td>{{ $tab === 'jobs' ? $l->applications_count : $l->proposals_count }}</td><td><x-status :status="$l->status" /></td>
        <td><form method="POST" action="{{ route('admin.listings.status', [$tab === 'jobs' ? 'job' : 'project', $l->id]) }}" class="row" style="flex-wrap:nowrap">@csrf<select class="select" name="status" style="width:auto">@foreach(($tab === 'jobs' ? ['open', 'closed', 'draft'] : ['open', 'cancelled', 'draft']) as $s)<option @selected($l->status === $s)>{{ $s }}</option>@endforeach</select><button class="btn btn-sm">Set</button></form></td></tr>
@endforeach
</tbody></table></div>
{{ ($tab === 'jobs' ? $jobs : $projects)->links() }}
@endsection
