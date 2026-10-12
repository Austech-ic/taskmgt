@extends('layouts.app')
@section('title', 'My profile')
@section('content')
<x-page-head title="My profile" subtitle="This is your public pitch. Keep it sharp, honest and specific." />
@include('app.talent.profile._nav')
<form method="POST" action="{{ route('app.profile.update') }}" enctype="multipart/form-data" class="split">
    @csrf @method('PUT')
    <div class="stack" style="--stack:20px">
        <div class="card" id="ai" x-data="aiAssist('profile_bio', function () { return { notes: this.$refs.notes.value }; }, function (r) { this.$refs.headline.value = r.headline; this.$refs.bio.value = r.bio; })">
            <div class="card-head"><h3>Intro</h3>
                <button type="button" class="btn btn-sm ai-btn" @click="run()" :disabled="loading"><i data-lucide="sparkles"></i><span x-text="loading ? 'Cooking…' : 'Glow-up with AI'"></span></button>
            </div>
            <div class="ai-panel mb-2" x-show="loading || error || result" x-cloak>
                <p class="small mb-1" x-show="loading">Reading your skills, experience and projects… ✨</p>
                <p class="small error mb-1" x-show="error" x-text="error"></p>
                <p class="small mb-0" x-show="result && !loading">Draft added below — edit it until it sounds like you, then save.</p>
            </div>
            <div class="field"><label for="notes" class="small muted">Anything AI should know? (optional)</label><input id="notes" x-ref="notes" class="input" placeholder="e.g. I love fintech, mentor juniors, built 3 apps with 100k+ users"></div>
            <div class="form-grid">
                <div class="field"><label for="name">Full name</label><input id="name" class="input" name="name" value="{{ old('name', $user->name) }}" required></div>
                <div class="field"><label for="primary_role">Role</label><input id="primary_role" class="input" name="primary_role" value="{{ old('primary_role', $profile->primary_role) }}" placeholder="Backend Engineer"></div>
                <div class="field span-2"><label for="headline">Headline</label><input id="headline" x-ref="headline" class="input" name="headline" maxlength="160" value="{{ old('headline', $profile->headline) }}" placeholder="Backend engineer building payment APIs that don’t flinch"></div>
                <div class="field span-2"><label for="bio">Bio</label><textarea id="bio" x-ref="bio" class="textarea" name="bio" rows="7" maxlength="3000">{{ old('bio', $profile->bio) }}</textarea><span class="hint">Lead with what you build, for whom, and the results. 2 short paragraphs is perfect.</span></div>
            </div>
        </div>

        <div class="card">
            <h3>Work preferences</h3>
            <div class="form-grid">
                <div class="field"><label for="availability">Availability</label><select id="availability" class="select" name="availability">@foreach(\App\Support\Labels::AVAILABILITY as $k => $v)<option value="{{ $k }}" @selected(old('availability', $profile->availability) === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="work_arrangement">Work arrangement</label><select id="work_arrangement" class="select" name="work_arrangement">@foreach(\App\Support\Labels::ARRANGEMENTS as $k => $v)<option value="{{ $k }}" @selected(old('work_arrangement', $profile->work_arrangement) === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="years_experience">Years of experience</label><input id="years_experience" type="number" min="0" max="50" class="input" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}"></div>
                <div class="field"><label for="languages">Languages</label><input id="languages" class="input" name="languages" value="{{ old('languages', $profile->languages) }}" placeholder="English, Yoruba"></div>
                <div class="field"><label for="city">City</label><input id="city" class="input" name="city" value="{{ old('city', $profile->city) }}"></div>
                <div class="field"><label for="country">Country</label><input id="country" class="input" name="country" value="{{ old('country', $profile->country) }}" required></div>
                <div class="field span-2"><label for="timezone">Time zone</label><select id="timezone" class="select" name="timezone">@foreach(timezone_identifiers_list() as $tz)<option value="{{ $tz }}" @selected(old('timezone', $profile->timezone) === $tz)>{{ $tz }}</option>@endforeach</select></div>
            </div>
            <div class="divider"></div>
            <h4>Rate / salary expectation</h4>
            <div class="form-grid">
                <div class="field"><label for="currency">Currency</label><select id="currency" class="select" name="currency">@foreach(\App\Support\Labels::CURRENCIES as $k => $v)<option value="{{ $k }}" @selected($profile->currency === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="rate_unit">Per</label><select id="rate_unit" class="select" name="rate_unit">@foreach(['month' => 'Month (salary)', 'hour' => 'Hour', 'project' => 'Project'] as $k => $v)<option value="{{ $k }}" @selected($profile->rate_unit === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="rate_min">From</label><input id="rate_min" class="input" name="rate_min" inputmode="decimal" value="{{ old('rate_min', to_major($profile->rate_min)) }}"></div>
                <div class="field"><label for="rate_max">To</label><input id="rate_max" class="input" name="rate_max" inputmode="decimal" value="{{ old('rate_max', to_major($profile->rate_max)) }}"></div>
            </div>
            <label class="check"><input type="checkbox" name="show_rate" value="1" @checked($profile->show_rate)> Show my rate publicly (otherwise hidden until you share it)</label>
        </div>

        <div class="card">
            <h3>Links</h3>
            <div class="form-grid">
                @foreach(['github_url' => 'GitHub', 'gitlab_url' => 'GitLab', 'linkedin_url' => 'LinkedIn', 'portfolio_url' => 'Personal site', 'behance_url' => 'Behance / Dribbble / Figma'] as $f => $l)
                    <div class="field"><label for="{{ $f }}">{{ $l }}</label><input id="{{ $f }}" type="url" class="input" name="{{ $f }}" value="{{ old($f, $profile->$f) }}" placeholder="https://"></div>
                @endforeach
            </div>
        </div>
    </div>

    <aside class="stack sticky" style="--stack:16px">
        <div class="card center">
            <img class="avatar avatar-xl" style="margin:0 auto" src="{{ $user->avatarUrl() }}" alt="">
            <label class="btn btn-sm mt-2" style="cursor:pointer"><i data-lucide="camera"></i> Change photo<input type="file" name="avatar" accept="image/*" hidden></label>
            <p class="hint mt-1 mb-0">A clear, friendly face photo works best.</p>
        </div>
        <div class="card">
            <h3>Privacy & contact</h3>
            <div class="field"><label for="contact_email">Contact email</label><input id="contact_email" type="email" class="input" name="contact_email" value="{{ old('contact_email', $profile->contact_email) }}"></div>
            <div class="field"><label for="contact_phone">Contact phone</label><input id="contact_phone" class="input" name="contact_phone" value="{{ old('contact_phone', $profile->contact_phone) }}"></div>
            <label class="check mb-1"><input type="checkbox" name="allow_direct_contact" value="1" @checked($profile->allow_direct_contact)> Let employers who unlock me see these</label>
            <label class="check"><input type="checkbox" name="is_public" value="1" @checked($profile->is_public)> Show my profile in search (once approved)</label>
            <div class="divider"></div>
            <div class="field mb-0"><label for="cv">CV (PDF/DOC, private)</label><input id="cv" type="file" class="input" name="cv" accept=".pdf,.doc,.docx">
                @if($profile->cv_path)<a class="small link mt-1" href="{{ route('app.profile.cv') }}">Download current CV</a>@endif</div>
        </div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Save profile</button>
    </aside>
</form>
@endsection
