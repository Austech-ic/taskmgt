@php($verified = $profile->verifiedSkills()->sortByDesc('pivot.verified_level'))
<article class="card card-hover talent-card">
    <div class="top">
        <img class="avatar avatar-lg" src="{{ $profile->user->avatarUrl() }}" alt="" loading="lazy">
        <div class="grow">
            <h3><a href="{{ route('talent.show', $profile) }}">{{ $profile->user->name }}</a>
                @if($verified->isNotEmpty())<span class="verified-tick" title="Has assessment-verified skills"><i data-lucide="check"></i></span>@endif
            </h3>
            <div class="small muted truncate">{{ $profile->primary_role ?: 'Tech professional' }}</div>
        </div>
    </div>
    <p class="clamp-2 ink-2 mb-0" style="font-size:.93rem">{{ $profile->headline }}</p>
    <div class="chips">
        @foreach($verified->take(3) as $skill)
            <span class="chip chip-verified"><i data-lucide="badge-check"></i>{{ $skill->name }} · L{{ $skill->pivot->verified_level }}</span>
        @endforeach
        @foreach($profile->skills->diff($verified)->take(max(0, 4 - $verified->count())) as $skill)
            <span class="chip">{{ $skill->name }}</span>
        @endforeach
    </div>
    <div class="meta">
        @if($profile->location())<span><i data-lucide="map-pin"></i>{{ $profile->location() }}</span>@endif
        <span><i data-lucide="clock-3"></i>{{ $profile->years_experience }} yrs</span>
        <span><i data-lucide="laptop"></i>{{ \App\Support\Labels::ARRANGEMENTS[$profile->work_arrangement] ?? '' }}</span>
    </div>
    <div class="foot">
        <span class="row gap-xs small"><span class="dot {{ $profile->availability === 'unavailable' ? 'dot-gray' : '' }}"></span>{{ $profile->availabilityLabel() }}</span>
        @if($profile->show_rate && $profile->rateLabel())<span class="small strong money">{{ $profile->rateLabel() }}</span>@endif
    </div>
</article>
