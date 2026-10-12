@extends('layouts.app')
@section('title', $job->exists ? 'Edit job' : 'Post a job')
@section('content')
<x-page-head :title="$job->exists ? 'Edit job' : 'Post a job'" subtitle="Clear, specific posts get better applicants. Skip the “rockstar ninja” stuff." />
<form method="POST" action="{{ $job->exists ? route('app.jobs.update', $job) : route('app.jobs.store') }}" class="split"
      x-data="aiAssist('job_description', function () { return { title: this.$refs.title.value, seniority: this.$refs.seniority.value, employment_type: this.$refs.type.value, work_arrangement: this.$refs.arr.value, location: this.$refs.loc.value, notes: this.$refs.notes.value, skills: [...document.querySelectorAll('input[name=&quot;skills[]&quot;]:checked')].map(i => i.dataset.name).join(', ') }; }, function (r) { this.$refs.summary.value = r.summary; this.$refs.description.value = r.description; this.$refs.resp.value = r.responsibilities; this.$refs.benefits.value = r.benefits; })">
    @csrf @if($job->exists) @method('PUT') @endif
    <div class="stack" style="--stack:20px">
        <div class="card">
            <div class="form-grid">
                <div class="field span-2"><label for="title">Job title</label><input id="title" x-ref="title" class="input input-lg" name="title" value="{{ old('title', $job->title) }}" required placeholder="Senior Backend Engineer (Laravel)"></div>
                <div class="field"><label for="seniority">Seniority</label><select id="seniority" x-ref="seniority" class="select" name="seniority">@foreach(\App\Support\Labels::SENIORITY as $k => $v)<option value="{{ $k }}" @selected(old('seniority', $job->seniority ?? 'mid') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="employment_type">Type</label><select id="employment_type" x-ref="type" class="select" name="employment_type">@foreach(\App\Support\Labels::EMPLOYMENT_TYPES as $k => $v)<option value="{{ $k }}" @selected(old('employment_type', $job->employment_type ?? 'full_time') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="work_arrangement">Arrangement</label><select id="work_arrangement" x-ref="arr" class="select" name="work_arrangement">@foreach(['remote' => 'Remote', 'hybrid' => 'Hybrid', 'onsite' => 'On-site'] as $k => $v)<option value="{{ $k }}" @selected(old('work_arrangement', $job->work_arrangement ?? 'remote') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="location">Location</label><input id="location" x-ref="loc" class="input" name="location" value="{{ old('location', $job->location) }}" placeholder="Lagos, Nigeria"></div>
            </div>
        </div>
        <div class="ai-panel">
            <div class="row-between"><div class="row"><span class="sparkle"><i data-lucide="sparkles"></i></span><strong>Draft the post with AI</strong></div>
                <button type="button" class="btn btn-sm ai-btn" @click="run()" :disabled="loading"><i data-lucide="wand-sparkles"></i><span x-text="loading ? 'Writing…' : 'Write it for me'"></span></button></div>
            <textarea x-ref="notes" class="textarea mt-2" rows="2" placeholder="Rough notes: what they’ll build, team, must-haves, perks…"></textarea>
            <p class="error small mb-0 mt-1" x-show="error" x-text="error"></p>
            <p class="small muted mb-0 mt-1" x-show="result && !loading">Draft inserted below — review, tweak, publish.</p>
        </div>
        <div class="card">
            <div class="field"><label for="summary">One-line summary</label><input id="summary" x-ref="summary" class="input" name="summary" maxlength="300" value="{{ old('summary', $job->summary) }}"></div>
            <div class="field"><label for="description">Description</label><textarea id="description" x-ref="description" class="textarea" name="description" rows="8" required>{{ old('description', $job->description) }}</textarea></div>
            <div class="field"><label for="responsibilities">Responsibilities <span class="muted">(one per line)</span></label><textarea id="responsibilities" x-ref="resp" class="textarea" name="responsibilities" rows="5">{{ old('responsibilities', $job->responsibilities) }}</textarea></div>
            <div class="field"><label for="benefits">Benefits <span class="muted">(one per line)</span></label><textarea id="benefits" x-ref="benefits" class="textarea" name="benefits" rows="4">{{ old('benefits', $job->benefits) }}</textarea></div>
        </div>
        <div class="card">
            <h3>Skills</h3>
            <p class="small muted">Tick the skills, then mark which are required. Candidates see which of yours they’ve verified.</p>
            @foreach($categories as $cat)
                <div class="small strong mt-2 mb-1">{{ $cat->name }}</div>
                <div class="chips">@foreach($cat->skills as $s)<label class="chip"><input type="checkbox" name="skills[]" value="{{ $s->id }}" data-name="{{ $s->name }}" @checked(array_key_exists($s->id, $selected))>{{ $s->name }}</label>@endforeach</div>
            @endforeach
            <div class="field mt-3"><label>Required (must-have) skills</label>
                <select class="select" name="required_skills[]" multiple size="5">@foreach($categories->flatMap->skills as $s)<option value="{{ $s->id }}" @selected(($selected[$s->id] ?? false))>{{ $s->name }}</option>@endforeach</select>
                <span class="hint">Hold Ctrl/Cmd to pick several. Unticked skills count as nice-to-have.</span></div>
        </div>
    </div>
    <aside class="sticky stack" style="--stack:16px">
        <div class="card">
            <h3>Pay</h3>
            <div class="form-grid">
                <div class="field span-2"><label for="currency">Currency</label><select id="currency" class="select" name="currency">@foreach(\App\Support\Labels::CURRENCIES as $k => $v)<option value="{{ $k }}" @selected(old('currency', $job->currency) === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="salary_min">Min / month</label><input id="salary_min" class="input" name="salary_min" inputmode="decimal" value="{{ old('salary_min', to_major($job->salary_min)) }}"></div>
                <div class="field"><label for="salary_max">Max / month</label><input id="salary_max" class="input" name="salary_max" inputmode="decimal" value="{{ old('salary_max', to_major($job->salary_max)) }}"></div>
            </div>
            <label class="check"><input type="checkbox" name="show_salary" value="1" @checked(old('show_salary', $job->show_salary))> Show salary range (more applicants 📈)</label>
        </div>
        <div class="card">
            <h3>Settings</h3>
            <div class="field"><label for="openings">Openings</label><input id="openings" type="number" min="1" class="input" name="openings" value="{{ old('openings', $job->openings) }}"></div>
            <div class="field"><label for="deadline">Deadline</label><input id="deadline" type="date" class="input" name="deadline" value="{{ old('deadline', $job->deadline?->toDateString()) }}"></div>
            <label class="check mb-2"><input type="checkbox" name="require_verified" value="1" @checked(old('require_verified', $job->require_verified))> Only candidates verified in required skills can apply</label>
            <div class="field"><label for="status">Status</label><select id="status" class="select" name="status">@foreach(['open' => 'Open (live)', 'draft' => 'Draft', 'closed' => 'Closed'] as $k => $v)<option value="{{ $k }}" @selected(old('status', $job->status) === $k)>{{ $v }}</option>@endforeach</select></div>
            <button class="btn btn-accent btn-lg btn-block">{{ $job->exists ? 'Save changes' : 'Publish job' }}</button>
        </div>
    </aside>
</form>
@endsection
