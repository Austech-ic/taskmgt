@extends('layouts.admin')
@section('title', 'Companies')
@section('content')
<x-page-head title="Companies" subtitle="Verify employers so talent can trust who’s reaching out." />
<form class="row mb-3"><input class="input" style="max-width:280px" name="q" value="{{ request('q') }}" placeholder="Company name"><label class="check"><input type="checkbox" name="pending" value="1" @checked(request('pending'))> Unverified only</label><button class="btn">Filter</button></form>
<div class="table-wrap"><table class="table"><thead><tr><th>Company</th><th>Owner</th><th>Activity</th><th>Credits</th><th>Verified</th></tr></thead><tbody>
@foreach($orgs as $o)
    <tr><td><strong>{{ $o->name }}</strong><div class="small muted">{{ $o->industry }} · {{ $o->city }} @if($o->website)· <a class="link" href="{{ $o->website }}" target="_blank" rel="noopener nofollow">site ↗</a>@endif @if($o->registration_number)· RC {{ $o->registration_number }}@endif</div></td>
        <td class="small">{{ $o->owner->name }}<br><span class="muted">{{ $o->owner->email }}</span></td>
        <td class="small">{{ $o->jobs_count }} jobs · {{ $o->unlocks_count }} unlocks · {{ $o->members_count }} members</td>
        <td><form method="POST" action="{{ route('admin.organizations.credits', $o) }}" class="row" style="flex-wrap:nowrap" x-data="{ o: false }">@csrf<strong>{{ $o->unlock_credits }}</strong><button type="button" class="btn btn-sm btn-ghost" @click="o = !o">±</button><span x-show="o" x-cloak class="row" style="flex-wrap:nowrap"><input class="input" type="number" name="credits" style="width:80px" placeholder="+5"><input class="input" name="reason" placeholder="Reason" required><button class="btn btn-sm">Apply</button></span></form></td>
        <td><form method="POST" action="{{ route('admin.organizations.verify', $o) }}">@csrf<button class="btn btn-sm {{ $o->verified_at ? '' : 'btn-primary' }}">{{ $o->verified_at ? 'Verified ✓ (undo)' : 'Verify' }}</button></form></td></tr>
@endforeach
</tbody></table></div>
{{ $orgs->links() }}
@endsection
