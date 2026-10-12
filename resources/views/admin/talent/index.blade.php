@extends('layouts.admin')
@section('title', 'Talent approvals')
@section('content')
<x-page-head title="Talent approvals" subtitle="Review profiles for completeness and authenticity. Identity and skill verification are separate." />
<div class="row-between mb-3">
    <div class="tabs-pill"><a href="{{ route('admin.talent.index') }}" @class(['is-active' => $status === 'queue'])>Queue</a>@foreach($statuses as $k => $v)<a href="{{ route('admin.talent.index', ['status' => $k]) }}" @class(['is-active' => $status === $k])>{{ $v }}</a>@endforeach<a href="{{ route('admin.talent.index', ['status' => 'all']) }}" @class(['is-active' => $status === 'all'])>All</a></div>
    <form><input type="hidden" name="status" value="{{ $status }}"><input class="input" name="q" value="{{ request('q') }}" placeholder="Search name/email"></form>
</div>
@if($profiles->isEmpty())
    <x-empty icon="user-check" title="Nothing here" text="No profiles in this view." />
@else
<div class="table-wrap"><table class="table"><thead><tr><th>Professional</th><th>Status</th><th>Evidence</th><th>Submitted</th><th></th></tr></thead><tbody>
@foreach($profiles as $p)
    <tr><td><div class="row"><img class="avatar avatar-sm" src="{{ $p->user->avatarUrl() }}" alt=""><div><strong>{{ $p->user->name }}</strong><div class="small muted">{{ $p->primary_role }} · {{ $p->user->email }}</div></div></div></td>
        <td><x-status :status="$p->status" :label="$p->statusLabel()" /></td>
        <td class="small">{{ $p->verified_count }} verified skills · {{ $p->portfolio_count }} projects · {{ $p->experiences_count }} roles @if($p->identity_verified_at)· <span class="badge badge-green">ID</span>@endif</td>
        <td class="small nowrap">{{ $p->submitted_at?->diffForHumans() ?? '—' }}</td>
        <td class="actions"><a class="btn btn-sm btn-primary" href="{{ route('admin.talent.show', $p) }}">Review</a></td></tr>
@endforeach
</tbody></table></div>
{{ $profiles->links() }}
@endif
@endsection
