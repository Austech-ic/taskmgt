@extends('layouts.site')
@section('title', $profile->user->name.' — '.($profile->primary_role ?: 'Tech professional'))
@section('description', $profile->headline)

@php
    $verified = $profile->verifiedSkills()->sortByDesc('pivot.verified_level');
    $unverified = $profile->skills->diff($verified);
    $isHirer = auth()->check() && auth()->user()->is_hirer;
@endphp

@section('content')
<section class="section-tight">
    <div class="container">
        @if($isOwner && ! $profile->isDiscoverable())
            <div class="alert alert-info mb-3"><i data-lucide="eye-off"></i><div>Only you can see this preview. Your profile is <strong>{{ strtolower($profile->statusLabel()) }}</strong> — it goes public once approved. <a class="link" href="{{ route('app.dashboard') }}">See what’s left →</a></div></div>
        @endif

        <div class="split">
            <div class="stack" style="--stack:20px">
                <div class="profile-hero">
                    <div class="profile-cover"></div>
                    <div class="profile-body">
                        <div class="row-between row-top">
                            <img class="avatar avatar-xl" src="{{ $profile->user->avatarUrl() }}" alt="{{ $profile->user->name }}">
                            <div class="row" style="margin-top:68px">
                                <button class="btn btn-sm" data-copy="{{ route('talent.show', $profile) }}"><i data-lucide="link"></i> Copy link</button>
                                @if($isOwner)<a class="btn btn-sm btn-primary" href="{{ route('app.profile.edit') }}"><i data-lucide="pencil"></i> Edit</a>@endif
                            </div>
                        </div>
                        <h1 class="mt-2 mb-0" style="font-size:clamp(1.8rem,3.6vw,2.6rem)">{{ $profile->user->name }}
                            @if($verified->isNotEmpty())<span class="verified-tick" style="width:26px;height:26px" title="Assessment-verified"><i data-lucide="check"></i></span>@endif
                        </h1>
                        <div class="muted strong">{{ $profile->primary_role }}</div>
                        <p class="mt-2" style="font-size:1.1rem;max-width:60ch">{{ $profile->headline }}</p>
                        <div class="row small muted gap-lg">
                            @if($profile->location())<span class="row gap-xs"><i data-lucide="map-pin" style="width:16px"></i>{{ $profile->location() }}</span>@endif
                            <span class="row gap-xs"><i data-lucide="clock-3" style="width:16px"></i>{{ $profile->years_experience }} years experience</span>
                            <span class="row gap-xs"><i data-lucide="globe-2" style="width:16px"></i>{{ $profile->timezone }}</span>
                            @if($profile->languages)<span class="row gap-xs"><i data-lucide="languages" style="width:16px"></i>{{ $profile->languages }}</span>@endif
                        </div>
                    </div>
                </div>

                {{-- Verified skills --}}
                <div class="card">
                    <div class="card-head"><h3>Skills & evidence</h3><a class="small link" href="{{ route('pages.show', 'verification') }}">How verification works</a></div>
                    @forelse($verified as $skill)
                        <div class="skill-row">
                            <span class="icon-tile sm" style="background:var(--green-soft);color:var(--green)"><i data-lucide="badge-check"></i></span>
                            <div class="grow">
                                <div class="row-between"><strong>{{ $skill->name }}</strong><x-level :level="$skill->pivot->verified_level" /></div>
                                <div class="row small muted gap-sm mt-1">
                                    <span class="levels">@for($i = 1; $i <= 5; $i++)<i @class(['on' => $i <= $skill->pivot->verified_level])></i>@endfor</span>
                                    <span>Devhail assessment · {{ \Illuminate\Support\Carbon::parse($skill->pivot->verified_at)->format('M Y') }}</span>
                                    @if($skill->pivot->verified_score)<span>· score band {{ floor($skill->pivot->verified_score / 10) * 10 }}–{{ min(100, floor($skill->pivot->verified_score / 10) * 10 + 9) }}%</span>@endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="muted mb-0">No assessment-verified skills yet.</p>
                    @endforelse
                    @if($unverified->isNotEmpty())
                        <div class="divider"></div>
                        <div class="small muted mb-1">Self-reported (not yet verified)</div>
                        <div class="chips">@foreach($unverified as $s)<span class="chip">{{ $s->name }} · {{ $s->pivot->years }}y</span>@endforeach</div>
                    @endif
                </div>

                @if($profile->bio)
                    <div class="card"><h3>About</h3><div class="prose" style="font-size:1rem">{!! nl2br(e($profile->bio)) !!}</div></div>
                @endif

                @if($profile->portfolio->isNotEmpty())
                    <div class="card">
                        <h3>Portfolio</h3>
                        <div class="grid-2 mt-2">
                            @foreach($profile->portfolio as $item)
                                <div class="card card-soft">
                                    @if($item->image_path)<img src="{{ Storage::disk('public')->url($item->image_path) }}" alt="" style="border-radius:12px;margin-bottom:12px;aspect-ratio:16/9;object-fit:cover;width:100%">@endif
                                    <div class="row-between"><strong>{{ $item->title }}</strong>@if($item->verified_at)<span class="badge badge-green"><i data-lucide="check"></i> Verified</span>@endif</div>
                                    @if($item->role)<div class="small muted">{{ $item->role }}</div>@endif
                                    <p class="small mt-1 mb-1">{{ $item->description }}</p>
                                    @if($item->outcome)<p class="small mb-1"><strong>Outcome:</strong> {{ $item->outcome }}</p>@endif
                                    @if($item->technologies)<div class="tag-list mb-1">@foreach(explode(',', $item->technologies) as $t)<span class="tag">{{ trim($t) }}</span>@endforeach</div>@endif
                                    <div class="row small">
                                        @if($item->url)<a class="link" href="{{ $item->url }}" target="_blank" rel="noopener nofollow">Live ↗</a>@endif
                                        @if($item->repo_url)<a class="link" href="{{ $item->repo_url }}" target="_blank" rel="noopener nofollow">Code ↗</a>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($profile->experiences->isNotEmpty())
                    <div class="card">
                        <h3>Experience & education</h3>
                        <ul class="timeline mt-2">
                            @foreach($profile->experiences as $exp)
                                <li>
                                    <span class="node {{ $exp->is_current ? 'violet' : '' }}"><i data-lucide="{{ $exp->kind === 'education' ? 'graduation-cap' : 'briefcase' }}"></i></span>
                                    <strong>{{ $exp->title }}</strong> · {{ $exp->organization }}
                                    <div class="small muted">{{ $exp->started_on?->format('M Y') }} – {{ $exp->is_current ? 'Present' : $exp->ended_on?->format('M Y') }} @if($exp->location) · {{ $exp->location }} @endif</div>
                                    @if($exp->description)<p class="small mt-1 mb-0 ink-2">{{ $exp->description }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($profile->certifications->isNotEmpty())
                    <div class="card">
                        <h3>Certifications</h3>
                        <div class="list-divided">
                            @foreach($profile->certifications as $c)
                                <div class="row-between"><div><strong>{{ $c->name }}</strong><div class="small muted">{{ $c->issuer }} @if($c->issued_on)· {{ $c->issued_on->format('M Y') }}@endif</div></div>
                                    @if($c->verified_at)<span class="badge badge-green">Credential verified</span>@else<span class="badge">Self-reported</span>@endif</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-head"><h3>Client reviews</h3>@if($stats['reviews'])<span class="badge badge-lime">★ {{ $stats['rating'] }} · {{ $stats['reviews'] }} {{ \Illuminate\Support\Str::plural('review', $stats['reviews']) }}</span>@endif</div>
                    @forelse($reviews as $r)
                        <div class="skill-row" style="align-items:flex-start">
                            <img class="avatar" src="{{ $r->reviewer->avatarUrl() }}" alt="">
                            <div class="grow"><div class="row-between"><strong>{{ $r->contract->title }}</strong><span class="small">{{ str_repeat('★', (int) round($r->overall)) }}<span class="muted">{{ str_repeat('★', 5 - (int) round($r->overall)) }}</span></span></div>
                                <p class="small mb-1 mt-1">{{ $r->comment }}</p>
                                @if($r->response)<p class="small muted mb-0"><strong>Response:</strong> {{ $r->response }}</p>@endif
                                <div class="small muted">{{ $r->created_at->format('M Y') }} · Verified Devhail contract</div></div>
                        </div>
                    @empty
                        <p class="muted mb-0">No completed Devhail contracts yet — reviews appear here after verified engagements only.</p>
                    @endforelse
                </div>
            </div>

            {{-- Sidebar --}}
            <aside class="sticky stack" style="--stack:16px">
                <div class="card card-pop">
                    <div class="row gap-xs mb-2"><span class="dot {{ $profile->availability === 'unavailable' ? 'dot-gray' : '' }}"></span><strong>{{ $profile->availabilityLabel() }}</strong></div>
                    <div class="small muted">{{ \App\Support\Labels::ARRANGEMENTS[$profile->work_arrangement] ?? '' }}</div>
                    @if($profile->show_rate && $profile->rateLabel())
                        <div class="mt-2"><div class="small muted">Rate / salary expectation</div><div style="font:800 1.5rem var(--display)" class="money">{{ $profile->rateLabel() }}</div></div>
                    @endif
                    <div class="divider"></div>
                    <div class="grid-2" style="gap:12px">
                        <div><div class="small muted">Completed</div><strong>{{ $stats['completed'] }} contracts</strong></div>
                        <div><div class="small muted">On time</div><strong>{{ $stats['on_time'] !== null ? $stats['on_time'].'%' : '—' }}</strong></div>
                        <div><div class="small muted">Identity</div><strong>{{ $profile->identity_verified_at ? 'Verified' : 'Not yet' }}</strong></div>
                        <div><div class="small muted">Last verified</div><strong>{{ $profile->last_verified_at?->format('M Y') ?? '—' }}</strong></div>
                    </div>
                    <div class="divider"></div>

                    {{-- Contact --}}
                    @if($isOwner)
                        <p class="small muted mb-0">This is how employers see your card. Contact details show only after an authorised unlock.</p>
                    @elseif($unlocked && $profile->allow_direct_contact)
                        <div class="alert alert-success mb-2"><i data-lucide="unlock"></i><div><strong>Contact unlocked</strong><br>
                            @if($profile->contact_email)<a href="mailto:{{ $profile->contact_email }}">{{ $profile->contact_email }}</a><br>@endif
                            @if($profile->contact_phone)<a href="tel:{{ $profile->contact_phone }}">{{ $profile->contact_phone }}</a>@endif
                            @if($profile->cv_path)<br><a class="link" href="{{ route('app.talent.cv', $profile) }}">Download CV</a>@endif
                        </div></div>
                        <form method="POST" action="{{ route('app.messages.start', $profile) }}">@csrf<button class="btn btn-primary btn-block"><i data-lucide="message-square"></i> Message on {{ setting('site_name') }}</button></form>
                    @elseif($org)
                        @if($profile->allow_direct_contact)
                            <form method="POST" action="{{ route('app.talent.unlock', $profile) }}" data-confirm="Use 1 contact unlock to reveal {{ $profile->user->firstName() }}’s contact details?">
                                @csrf
                                <button class="btn btn-accent btn-block"><i data-lucide="unlock"></i> Unlock contact ({{ $org->unlocksAvailable() }} left)</button>
                            </form>
                            <div class="locked small mt-1">email@example.com · +234 800 000 0000</div>
                        @else
                            <p class="small muted">{{ $profile->user->firstName() }} prefers to be contacted through {{ setting('site_name') }}.</p>
                        @endif
                        <div x-data="{ o: false }" class="mt-1">
                            <button class="btn btn-block" @click="o = !o"><i data-lucide="send"></i> Send a contact request</button>
                            <form method="POST" action="{{ route('app.talent.request', $profile) }}" x-show="o" x-cloak class="mt-2">
                                @csrf
                                <div class="field"><input class="input" name="subject" placeholder="Senior React role at {{ $org->name }}" required maxlength="150"></div>
                                <div class="field"><textarea class="textarea" name="message" rows="4" placeholder="Hi {{ $profile->user->firstName() }}, we’re hiring…" required maxlength="2000"></textarea></div>
                                <button class="btn btn-primary btn-block">Send request</button>
                            </form>
                        </div>
                        @if($shortlists->isNotEmpty())
                            <form method="POST" action="{{ route('app.talent.shortlist', $profile) }}" class="row mt-2" style="flex-wrap:nowrap">
                                @csrf
                                <select name="shortlist_id" class="select" aria-label="Shortlist">@foreach($shortlists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select>
                                <button class="btn btn-icon" aria-label="Add to shortlist"><i data-lucide="bookmark-plus"></i></button>
                            </form>
                        @endif
                    @elseif($isHirer)
                        <a href="{{ route('app.company.edit') }}" class="btn btn-accent btn-block">Set up your company to connect</a>
                    @else
                        <a href="{{ route('register', ['as' => 'hirer']) }}" class="btn btn-accent btn-block"><i data-lucide="unlock"></i> Hire {{ $profile->user->firstName() }}</a>
                        <p class="small muted mt-1 mb-0 center">Free to search. Contact details unlock with a plan or credits.</p>
                    @endif
                </div>

                <div class="card card-soft">
                    <h4>Links</h4>
                    <div class="stack" style="--stack:8px">
                        @foreach(['github_url' => ['github', 'GitHub'], 'gitlab_url' => ['gitlab', 'GitLab'], 'linkedin_url' => ['linkedin', 'LinkedIn'], 'portfolio_url' => ['globe', 'Website'], 'behance_url' => ['palette', 'Behance / Dribbble']] as $field => [$icon, $label])
                            @if($profile->$field)<a class="row gap-sm" href="{{ $profile->$field }}" target="_blank" rel="noopener nofollow"><i data-lucide="{{ $icon }}" style="width:18px"></i> {{ $label }} <i data-lucide="arrow-up-right" style="width:14px"></i></a>@endif
                        @endforeach
                    </div>
                </div>
                <p class="small muted center">Verified skills are evidence of tested knowledge — check reviews and delivery history too.</p>
            </aside>
        </div>
    </div>
</section>
@endsection
