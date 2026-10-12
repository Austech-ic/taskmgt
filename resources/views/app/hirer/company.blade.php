@extends('layouts.app')
@section('title', 'Company')
@section('content')
<x-page-head title="{{ $org ? $org->name : 'Set up your company' }}" subtitle="Shown on your job posts. Verified companies get a badge and more applicants." />
<div class="split">
    <form method="POST" action="{{ route('app.company.update') }}" enctype="multipart/form-data" class="card">
        @csrf @method('PUT')
        <div class="row mb-2">
            <span class="logo-tile" style="width:64px;height:64px;font-size:1.5rem">@if($org?->logoUrl())<img src="{{ $org->logoUrl() }}" alt="">@else{{ $org ? mb_substr($org->name, 0, 1) : '?' }}@endif</span>
            <label class="btn btn-sm" style="cursor:pointer"><i data-lucide="image-up"></i> Logo<input type="file" name="logo" accept="image/*" hidden></label>
            @if($org?->isVerified())<span class="badge badge-violet"><i data-lucide="badge-check"></i> Verified company</span>@elseif($org)<span class="badge">Verification pending</span>@endif
        </div>
        <div class="form-grid">
            <div class="field span-2"><label for="name">Company name</label><input id="name" class="input" name="name" value="{{ old('name', $org?->name) }}" required></div>
            <div class="field"><label for="website">Website</label><input id="website" type="url" class="input" name="website" value="{{ old('website', $org?->website) }}" placeholder="https://"></div>
            <div class="field"><label for="industry">Industry</label><input id="industry" class="input" name="industry" value="{{ old('industry', $org?->industry) }}" placeholder="Fintech"></div>
            <div class="field"><label for="size">Team size</label><select id="size" class="select" name="size"><option value="">—</option>@foreach(['1-10', '11-50', '51-200', '201-1000', '1000+'] as $s)<option @selected(old('size', $org?->size) === $s)>{{ $s }}</option>@endforeach</select></div>
            <div class="field"><label for="registration_number">CAC / registration no.</label><input id="registration_number" class="input" name="registration_number" value="{{ old('registration_number', $org?->registration_number) }}"><span class="hint">Helps us verify you faster.</span></div>
            <div class="field"><label for="city">City</label><input id="city" class="input" name="city" value="{{ old('city', $org?->city) }}"></div>
            <div class="field"><label for="country">Country</label><input id="country" class="input" name="country" value="{{ old('country', $org?->country ?? 'Nigeria') }}" required></div>
            <div class="field span-2"><label for="about">About</label><textarea id="about" class="textarea" name="about" rows="4">{{ old('about', $org?->about) }}</textarea></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Save company</button></div>
    </form>
    @if($org)
        <aside class="card">
            <h3>Team</h3>
            <p class="small muted">{{ $members->count() }} / {{ $seats }} recruiter {{ \Illuminate\Support\Str::plural('seat', $seats) }} used</p>
            @foreach($members as $m)
                <div class="row mt-2"><img class="avatar avatar-sm" src="{{ $m->avatarUrl() }}" alt=""><span class="grow small"><strong>{{ $m->name }}</strong><br><span class="muted">{{ ucfirst($m->pivot->role) }}</span></span>
                    @if($org->owner_id === auth()->id() && $m->id !== $org->owner_id)<form method="POST" action="{{ route('app.company.members.remove', $m) }}" data-confirm="Remove {{ $m->name }}?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm btn-icon" aria-label="Remove"><i data-lucide="x"></i></button></form>@endif</div>
            @endforeach
            @if($org->owner_id === auth()->id())
                <form method="POST" action="{{ route('app.company.members.add') }}" class="mt-3">
                    @csrf
                    <div class="field"><label for="m_email">Add teammate by email</label><input id="m_email" type="email" class="input" name="email" required></div>
                    <div class="row" style="flex-wrap:nowrap"><select class="select" name="role"><option value="recruiter">Recruiter</option><option value="admin">Admin</option></select><button class="btn btn-sm">Add</button></div>
                </form>
            @endif
        </aside>
    @endif
</div>
@endsection
