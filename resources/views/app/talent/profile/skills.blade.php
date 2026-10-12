@extends('layouts.app')
@section('title', 'Skills')
@section('content')
<x-page-head title="Skills" subtitle="Claim skills from our taxonomy. Self-reported skills show as unverified until you pass the assessment." />
@include('app.talent.profile._nav')
<div class="split">
    <div class="card card-flush">
        @forelse($claimed as $ps)
            @php($assessment = $ps->skill->assessments->firstWhere('status', 'published'))
            <div class="row" style="padding:16px 20px;border-bottom:1px solid var(--line)" x-data="{ edit: false }">
                <span class="icon-tile sm {{ $ps->isVerified() ? '' : 'lime' }}" @if($ps->isVerified()) style="background:var(--green-soft);color:var(--green)" @endif><i data-lucide="{{ $ps->isVerified() ? 'badge-check' : 'circle-dashed' }}"></i></span>
                <div class="grow">
                    <div class="row gap-sm"><strong>{{ $ps->skill->name }}</strong>@if($ps->is_primary)<span class="badge badge-ink">Primary</span>@endif
                        @if($ps->isVerified())<x-level :level="$ps->verified_level" />@else<span class="badge">Self-reported · L{{ $ps->self_level }}</span>@endif</div>
                    <div class="small muted">{{ $ps->years }} {{ \Illuminate\Support\Str::plural('year', $ps->years) }} · {{ $ps->skill->category->name ?? '' }}
                        @if($ps->isVerified()) · verified {{ $ps->verified_at->format('M Y') }}, renews {{ $ps->verification_expires_at?->format('M Y') }}@endif</div>
                    <form method="POST" action="{{ route('app.profile.skills.update', $ps) }}" class="row mt-1" x-show="edit" x-cloak>
                        @csrf @method('PUT')
                        <select name="self_level" class="select" style="width:auto" aria-label="Self level">@foreach(\App\Support\Labels::LEVELS as $n => $l)<option value="{{ $n }}" @selected($ps->self_level === $n)>{{ $n }} · {{ $l['name'] }}</option>@endforeach</select>
                        <input name="years" type="number" min="0" max="40" class="input" style="width:90px" value="{{ $ps->years }}" aria-label="Years">
                        <label class="check"><input type="checkbox" name="is_primary" value="1" @checked($ps->is_primary)> Primary</label>
                        <button class="btn btn-sm btn-primary">Save</button>
                    </form>
                </div>
                @if($assessment && ! $ps->isVerified())<a href="{{ route('app.assessments.show', $assessment) }}" class="btn btn-sm btn-accent">Verify</a>@elseif(! $assessment && ! $ps->isVerified())<span class="small muted hide-mobile">Assessment coming soon</span>@endif
                <button class="btn btn-sm btn-ghost btn-icon" @click="edit = !edit" aria-label="Edit"><i data-lucide="pencil"></i></button>
                <form method="POST" action="{{ route('app.profile.skills.remove', $ps) }}" data-confirm="Remove {{ $ps->skill->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost btn-icon" aria-label="Remove"><i data-lucide="trash-2"></i></button></form>
            </div>
        @empty
            <div style="padding:24px"><x-empty icon="tags" title="No skills yet" text="Add your strongest skill first and mark it primary — that’s the one you’ll verify to go live." /></div>
        @endforelse
    </div>
    <aside class="sticky">
        <form method="POST" action="{{ route('app.profile.skills.add') }}" class="card card-pop">
            @csrf
            <h3>Add a skill</h3>
            <div class="field"><label for="skill_id">Skill</label>
                <select id="skill_id" name="skill_id" class="select" required>
                    <option value="">Choose…</option>
                    @foreach($categories as $cat)<optgroup label="{{ $cat->name }}">@foreach($cat->skills as $s)<option value="{{ $s->id }}" @disabled($claimed->pluck('skill_id')->contains($s->id))>{{ $s->name }}</option>@endforeach</optgroup>@endforeach
                </select></div>
            <div class="field"><label for="self_level">How strong are you? (honest!)</label><select id="self_level" name="self_level" class="select">@foreach(\App\Support\Labels::LEVELS as $n => $l)<option value="{{ $n }}" @selected($n === 3)>{{ $n }} · {{ $l['name'] }}</option>@endforeach</select></div>
            <div class="field"><label for="years">Years using it</label><input id="years" type="number" min="0" max="40" name="years" class="input" value="2"></div>
            <button class="btn btn-primary btn-block">Add skill</button>
            <p class="hint mt-2 mb-0">Don’t see your skill? Tell us via the contact page — the taxonomy grows with the community.</p>
        </form>
    </aside>
</div>
@endsection
