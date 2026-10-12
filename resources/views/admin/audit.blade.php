@extends('layouts.admin')
@section('title', 'Audit log')
@section('content')
<x-page-head title="Audit log" subtitle="Append-only trail of privileged, assessment and financial actions." />
<form class="row mb-2"><select class="select" name="action" style="width:auto" onchange="this.form.submit()"><option value="">All actions</option>@foreach($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select></form>
<div class="table-wrap"><table class="table"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Subject</th><th>Details</th><th>IP</th></tr></thead><tbody>
@foreach($logs as $l)
    <tr><td class="nowrap small">{{ $l->created_at->format('j M Y, H:i') }}</td><td class="small">{{ $l->user?->name ?? 'system' }}</td><td class="mono small">{{ $l->action }}</td><td class="small">{{ $l->subject_type ? class_basename($l->subject_type).' #'.$l->subject_id : '—' }}</td><td class="small mono" style="max-width:340px;word-break:break-all">{{ $l->meta ? json_encode($l->meta) : '' }}</td><td class="small muted">{{ $l->ip_address }}</td></tr>
@endforeach
</tbody></table></div>
{{ $logs->links() }}
@endsection
