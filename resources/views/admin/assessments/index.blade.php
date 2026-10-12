@extends('layouts.admin')
@section('title', 'Assessments')
@section('content')
<x-page-head title="Assessments" subtitle="Versioned, skill-specific tests. Retire questions that leak; add practical briefs for hands-on roles."><a href="{{ route('admin.assessments.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> New assessment</a></x-page-head>
<div class="table-wrap"><table class="table"><thead><tr><th>Assessment</th><th>Skill</th><th>Questions</th><th>Attempts</th><th>Pass rate</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($assessments as $a)
    <tr><td><strong>{{ $a->title }}</strong><div class="small muted">v{{ $a->version }} · {{ $a->duration_minutes }}m · pass {{ $a->pass_mark }}% @if($a->requires_review)· practical @endif</div></td><td>{{ $a->skill->name }}</td>
        <td>{{ $a->active_count }} active <span class="small muted">/ {{ $a->questions_count }}</span>@if($a->active_count < $a->question_count)<span class="badge badge-amber">needs {{ $a->question_count }}</span>@endif</td>
        <td>{{ $a->attempts_count }}</td><td>{{ $a->attempts_count ? round($a->passed_count / $a->attempts_count * 100).'%' : '—' }}</td><td><x-status :status="$a->status" /></td>
        <td class="actions"><a class="btn btn-sm" href="{{ route('admin.assessments.edit', $a) }}">Edit</a></td></tr>
@endforeach
</tbody></table></div>
@endsection
