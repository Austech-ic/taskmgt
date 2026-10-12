@extends('layouts.admin')
@section('title', 'Users')
@section('content')
<x-page-head title="Users & roles" subtitle="Least privilege: only super admins can grant admin or finance roles. Every change is audited." />
<form class="row mb-3"><input class="input" style="max-width:280px" name="q" value="{{ request('q') }}" placeholder="Name or email"><select class="select" name="role" style="width:auto"><option value="">Everyone</option>@foreach(['talent' => 'Talent', 'hirer' => 'Hirers', 'staff' => 'Staff'] as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $v }}</option>@endforeach</select><select class="select" name="status" style="width:auto"><option value="">Any status</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="suspended" @selected(request('status') === 'suspended')>Suspended</option></select><button class="btn">Filter</button></form>
<div class="table-wrap"><table class="table"><thead><tr><th>User</th><th>Workspaces</th><th>Joined</th><th>Staff role & status</th></tr></thead><tbody>
@foreach($users as $u)
    <tr><td><div class="row"><img class="avatar avatar-sm" src="{{ $u->avatarUrl() }}" alt=""><div><strong>{{ $u->name }}</strong><div class="small muted">{{ $u->email }}</div></div></div></td>
        <td>@if($u->is_talent)<span class="badge badge-lime">talent</span>@endif @if($u->is_hirer)<span class="badge badge-violet">hiring</span>@endif</td>
        <td class="small nowrap">{{ $u->created_at->format('j M Y') }}<br><span class="muted">seen {{ $u->last_seen_at?->diffForHumans() ?? 'never' }}</span></td>
        <td><form method="POST" action="{{ route('admin.users.update', $u) }}" class="row" style="flex-wrap:nowrap">@csrf @method('PUT')
            <select class="select" name="staff_role" style="width:auto"><option value="">Member</option>@foreach($roles as $k => $v)<option value="{{ $k }}" @selected($u->staff_role === $k)>{{ $v }}</option>@endforeach</select>
            <select class="select" name="status" style="width:auto"><option value="active" @selected($u->status === 'active')>Active</option><option value="suspended" @selected($u->status === 'suspended')>Suspended</option></select>
            <button class="btn btn-sm">Save</button></form></td></tr>
@endforeach
</tbody></table></div>
{{ $users->links() }}
@endsection
