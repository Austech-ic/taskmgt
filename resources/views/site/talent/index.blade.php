@extends('layouts.site')
@section('title', 'Find verified tech talent')
@section('description', 'Search assessment-verified developers, designers, cloud, data and security professionals.')

@section('content')
<section class="section-tight" style="padding-bottom:12px">
    <div class="container">
        <span class="eyebrow">Talent directory</span>
        <h1 style="font-size:clamp(2rem,4.5vw,3.2rem)">Find people who’ve <span class="highlight">proven it.</span></h1>
        <p class="ink-2" style="max-width:60ch">Every badge here was earned in a Devhail assessment. Filter by verified level, or just describe who you need.</p>

        <form method="POST" action="{{ route('talent.ai') }}" class="ai-panel mt-3">
            @csrf
            <div class="row">
                <span class="sparkle"><i data-lucide="sparkles"></i></span>
                <strong>Smart search</strong>
                @unless($aiEnabled)<span class="badge">keyword mode</span>@endunless
            </div>
            <div class="row mt-1" style="flex-wrap:nowrap">
                <input name="prompt" class="input input-lg grow" placeholder="e.g. mid-level Laravel dev with SQL, Abuja or remote, available now" maxlength="400" required aria-label="Describe who you need">
                <button class="btn ai-btn btn-lg" type="submit"><i data-lucide="wand-sparkles"></i><span class="hide-mobile">Search</span></button>
            </div>
        </form>
    </div>
</section>

<section class="section-tight" style="padding-top:12px">
    <div class="container split-left">
        <aside class="sticky" x-data="{ open: window.innerWidth > 980 }">
            <button class="btn btn-block mb-2 mobile-only" @click="open = !open" type="button"><i data-lucide="sliders-horizontal"></i> Filters</button>
            <form method="GET" class="card filter-panel" x-show="open" x-transition>
                <div class="field"><label for="q">Keywords</label><div class="input-group"><i data-lucide="search"></i><input id="q" class="input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, role, skill…"></div></div>
                <div class="field"><label for="skills">Skills</label>
                    <select id="skills" class="select" name="skills">
                        <option value="">Any skill</option>
                        @foreach($categories as $cat)
                            <optgroup label="{{ $cat->name }}">
                                @foreach($cat->skills as $s)<option value="{{ $s->name }}" @selected(($filters['skills'] ?? '') === $s->name)>{{ $s->name }}</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @if(! empty($filters['skills']) && str_contains($filters['skills'], ','))<span class="hint">Matching all of: {{ $filters['skills'] }}</span><input type="hidden" name="skills" value="{{ $filters['skills'] }}">@endif
                </div>
                <div class="field"><label for="min_level">Minimum verified level</label>
                    <select id="min_level" class="select" name="min_level">
                        <option value="">Any (incl. unverified)</option>
                        @foreach(\App\Support\Labels::LEVELS as $n => $l)<option value="{{ $n }}" @selected(($filters['min_level'] ?? '') == $n)>{{ $n }} · {{ $l['name'] }}+</option>@endforeach
                    </select>
                </div>
                <label class="check mb-2"><input type="checkbox" name="verified_only" value="1" @checked(! empty($filters['verified_only']))> Verified skills only</label>
                <div class="field"><label for="location">Location</label><input id="location" class="input" name="location" value="{{ $filters['location'] ?? '' }}" placeholder="Lagos, Nigeria…"></div>
                <div class="field"><label for="availability">Availability</label>
                    <select id="availability" class="select" name="availability"><option value="">Available (any)</option>@foreach(\App\Support\Labels::AVAILABILITY as $k => $v)<option value="{{ $k }}" @selected(($filters['availability'] ?? '') === $k)>{{ $v }}</option>@endforeach</select>
                </div>
                <div class="field"><label for="arrangement">Work arrangement</label>
                    <select id="arrangement" class="select" name="arrangement"><option value="">Any</option>@foreach(\App\Support\Labels::ARRANGEMENTS as $k => $v)@if($k !== 'any')<option value="{{ $k }}" @selected(($filters['arrangement'] ?? '') === $k)>{{ $v }}</option>@endif @endforeach</select>
                </div>
                <div class="field"><label for="min_years">Min. years experience</label><input id="min_years" type="number" min="0" max="40" class="input" name="min_years" value="{{ $filters['min_years'] ?? '' }}"></div>
                <div class="field"><label for="sort">Sort by</label>
                    <select id="sort" class="select" name="sort">
                        @foreach(['relevance' => 'Best evidence', 'recently_verified' => 'Recently verified', 'experience' => 'Most experience', 'newest' => 'Newest'] as $k => $v)<option value="{{ $k }}" @selected(($filters['sort'] ?? 'relevance') === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Apply filters</button>
                <a href="{{ route('talent.index') }}" class="btn btn-ghost btn-block mt-1">Reset</a>
            </form>
            @if($org)
                <form method="POST" action="{{ route('app.searches.store') }}" class="card card-soft mt-2" x-show="open">
                    @csrf
                    <input type="hidden" name="filters" value="{{ json_encode($filters) }}">
                    <div class="field mb-1"><label for="ss">Save this search</label><input id="ss" class="input" name="name" placeholder="Senior React, Lagos" required></div>
                    <button class="btn btn-sm btn-block" type="submit"><i data-lucide="bookmark-plus"></i> Save</button>
                    @foreach($savedSearches as $s)
                        <div class="row-between mt-1 small"><a class="link" href="{{ route('talent.index', $s->filters) }}">{{ $s->name }}</a></div>
                    @endforeach
                </form>
            @endif
        </aside>

        <div>
            <div class="row-between mb-2">
                <p class="muted mb-0"><strong class="ink-2">{{ $results->total() }}</strong> {{ \Illuminate\Support\Str::plural('professional', $results->total()) }} match</p>
                @if($org)<span class="small muted"><i data-lucide="unlock" style="display:inline;width:14px;vertical-align:-2px"></i> {{ $org->unlocksAvailable() }} unlocks left · <a class="link" href="{{ route('app.billing') }}">top up</a></span>@endif
            </div>
            @if($results->isEmpty())
                <x-empty icon="search-x" title="No one matches that (yet)" text="Try loosening a filter — or lower the minimum verified level.">
                    <a href="{{ route('talent.index') }}" class="btn">Clear filters</a>
                </x-empty>
            @else
                <div class="grid-auto" style="grid-template-columns:repeat(auto-fill,minmax(min(100%,300px),1fr))">
                    @foreach($results as $profile)
                        <div style="position:relative">
                            @include('partials.talent-card', ['profile' => $profile])
                            @if($org && $shortlists->isNotEmpty())
                                <form method="POST" action="{{ route('app.talent.shortlist', $profile) }}" class="row gap-xs" style="position:absolute;top:14px;right:14px" x-data="{ o: false }">
                                    @csrf
                                    <button type="button" class="btn btn-icon btn-sm" @click="o = !o" aria-label="Add to shortlist" title="Add to shortlist"><i data-lucide="bookmark-plus"></i></button>
                                    <div class="dropdown-menu" x-show="o" x-cloak @click.outside="o = false" style="top:40px">
                                        @foreach($shortlists as $list)<button name="shortlist_id" value="{{ $list->id }}" type="submit"><i data-lucide="list-plus"></i>{{ $list->name }}</button>@endforeach
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
                {{ $results->links() }}
            @endif
        </div>
    </div>
</section>
@endsection
