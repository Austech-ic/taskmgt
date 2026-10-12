@extends('layouts.admin')
@section('title', 'Review queue')
@section('content')
<x-page-head title="Assessment review queue" subtitle="Practical submissions and attempts flagged by integrity checks. Flags are signals, not verdicts." />
<div class="tabs-pill mb-3">@foreach(['under_review' => 'Needs review', 'passed' => 'Passed', 'not_passed' => 'Not passed', 'all' => 'All'] as $k => $v)<a href="{{ route('admin.attempts.index', ['status' => $k]) }}" @class(['is-active' => $status === $k])>{{ $v }}</a>@endforeach</div>
@if($attempts->isEmpty())
    <x-empty icon="party-popper" title="Queue clear" text="Nothing waiting. Go touch grass 🌱" />
@else
<div class="table-wrap"><table class="table"><thead><tr><th>Candidate</th><th>Assessment</th><th>Score</th><th>Flags</th><th>Submitted</th><th></th></tr></thead><tbody>
@foreach($attempts as $a)
    <tr><td><strong>{{ $a->user->name }}</strong><div class="small muted">{{ $a->user->email }}</div></td><td>{{ $a->assessment->title }} <span class="small muted">v{{ $a->assessment_version }}</span></td><td>{{ $a->percentage }}%</td>
        <td>@forelse($a->integrity_flags ?? [] as $f)<span class="badge badge-amber">{{ str_replace('_', ' ', $f) }}</span> @empty<span class="muted small">—</span>@endforelse @if($a->assessment->requires_review)<span class="badge badge-violet">practical</span>@endif</td>
        <td class="small nowrap">{{ $a->submitted_at?->diffForHumans() }}</td><td class="actions"><a class="btn btn-sm btn-primary" href="{{ route('admin.attempts.show', $a) }}">{{ $a->status === 'under_review' ? 'Review' : 'View' }}</a></td></tr>
@endforeach
</tbody></table></div>
{{ $attempts->links() }}
@endif
@endsection
