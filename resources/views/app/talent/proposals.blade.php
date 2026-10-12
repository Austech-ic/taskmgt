@extends('layouts.app')
@section('title', 'Proposals')
@section('content')
<x-page-head title="Proposals" subtitle="Your pitches to clients. Accepted ones become contracts."><a href="{{ route('projects.index') }}" class="btn btn-primary"><i data-lucide="search"></i> Find projects</a></x-page-head>
@if($proposals->isEmpty())
    <x-empty icon="file-text" title="No proposals yet" text="Find a project that fits your verified skills and send a sharp pitch. AI can help you draft it."><a href="{{ route('projects.index') }}" class="btn btn-accent">Browse projects</a></x-empty>
@else
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Project</th><th>Your price</th><th>Duration</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($proposals as $p)
            <tr><td><a class="strong" href="{{ route('projects.show', $p->project) }}">{{ $p->project->title }}</a><div class="small muted">{{ $p->created_at->diffForHumans() }}</div></td>
                <td class="money">{{ money($p->amount_minor, $p->project->currency) }}</td><td>{{ $p->duration_days }} days</td>
                <td><x-status :status="$p->status" /></td>
                <td class="actions">
                    @if($p->status === 'accepted' && $p->project->contract)<a class="btn btn-sm btn-primary" href="{{ route('app.contracts.show', $p->project->contract) }}">Open contract</a>@endif
                    @if(in_array($p->status, ['submitted', 'shortlisted']))<form method="POST" action="{{ route('app.proposals.withdraw', $p) }}" data-confirm="Withdraw this proposal?">@csrf<button class="btn btn-sm btn-ghost">Withdraw</button></form>@endif
                </td></tr>
        @endforeach</tbody>
    </table></div>
    {{ $proposals->links() }}
@endif
@endsection
