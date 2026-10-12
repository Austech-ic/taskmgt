@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<x-page-head title="Projects" subtitle="Briefs you’ve posted. Hire from proposals, then pay by milestone."><a href="{{ route('app.projects.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Post a project</a></x-page-head>
@if($projects->isEmpty())
    <x-empty icon="rocket" title="No projects yet" text="Describe your idea — AI turns it into a clear brief with milestones."><a href="{{ route('app.projects.create') }}" class="btn btn-accent">Post a project</a></x-empty>
@else
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Project</th><th>Budget</th><th>Proposals</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($projects as $p)
            <tr><td><strong>{{ $p->title }}</strong><div class="small muted">posted {{ $p->created_at->diffForHumans() }}</div></td><td class="money">{{ $p->budgetLabel() }}</td><td>{{ $p->open_proposals }}</td><td><x-status :status="$p->status" /></td>
                <td class="actions">@if($p->contract)<a class="btn btn-sm btn-primary" href="{{ route('app.contracts.show', $p->contract) }}">Contract</a>@else<a class="btn btn-sm btn-primary" href="{{ route('app.projects.proposals', $p) }}">Proposals</a>@endif
                    @unless(in_array($p->status, ['in_progress', 'completed']))<a class="btn btn-sm" href="{{ route('app.projects.edit', $p) }}">Edit</a>@endunless</td></tr>
        @endforeach</tbody>
    </table></div>
    {{ $projects->links() }}
@endif
@endsection
