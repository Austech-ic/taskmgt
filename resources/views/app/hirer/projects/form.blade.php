@extends('layouts.app')
@section('title', $project->exists ? 'Edit project' : 'Post a project')
@section('content')
<x-page-head :title="$project->exists ? 'Edit project' : 'Post a project'" subtitle="Testable deliverables + acceptance criteria = smooth milestones and zero drama." />
<form method="POST" action="{{ $project->exists ? route('app.projects.update', $project) : route('app.projects.store') }}" class="split"
      x-data="aiAssist('project_scope', function () { return { title: this.$refs.title.value, idea: this.$refs.description.value, budget: this.$refs.bmax.value, deadline: this.$refs.deadline.value }; }, function (r) { this.$refs.description.value = r.description; this.$refs.deliverables.value = r.deliverables; this.$refs.criteria.value = r.acceptance_criteria; })">
    @csrf @if($project->exists) @method('PUT') @endif
    <div class="stack" style="--stack:20px">
        <div class="card">
            <div class="field"><label for="title">Project title</label><input id="title" x-ref="title" class="input input-lg" name="title" value="{{ old('title', $project->title) }}" required placeholder="Build a booking website for my salon"></div>
            <div class="field"><div class="row-between"><label for="description">Describe it</label>
                <button type="button" class="btn btn-sm ai-btn" @click="run()" :disabled="loading"><i data-lucide="sparkles"></i><span x-text="loading ? 'Scoping…' : 'Scope it with AI'"></span></button></div>
                <textarea id="description" x-ref="description" class="textarea" name="description" rows="8" required minlength="40" placeholder="What do you need, who is it for, and what does success look like? Rough is fine — AI can tidy it up.">{{ old('description', $project->description) }}</textarea>
                <span class="error" x-show="error" x-text="error"></span></div>
            <div class="ai-panel" x-show="result && result.milestones && result.milestones.length" x-cloak>
                <strong>Suggested milestones</strong>
                <template x-for="m in (result ? result.milestones : [])"><div class="row-between small mt-1"><span x-text="m.title"></span><span class="badge" x-text="m.percent + '% of budget'"></span></div></template>
                <p class="small muted mb-0 mt-1">You’ll set exact milestones when you hire — this is just a starting point.</p>
            </div>
        </div>
        <div class="grid-2">
            <div class="card"><div class="field mb-0"><label for="deliverables">Deliverables <span class="muted">(one per line)</span></label><textarea id="deliverables" x-ref="deliverables" class="textarea" name="deliverables" rows="6">{{ old('deliverables', $project->deliverables) }}</textarea></div></div>
            <div class="card"><div class="field mb-0"><label for="acceptance_criteria">Acceptance criteria <span class="muted">(testable)</span></label><textarea id="acceptance_criteria" x-ref="criteria" class="textarea" name="acceptance_criteria" rows="6">{{ old('acceptance_criteria', $project->acceptance_criteria) }}</textarea></div></div>
        </div>
        <div class="card">
            <h3>Skills needed</h3>
            @foreach($categories as $cat)
                <div class="small strong mt-2 mb-1">{{ $cat->name }}</div>
                <div class="chips">@foreach($cat->skills as $s)<label class="chip"><input type="checkbox" name="skills[]" value="{{ $s->id }}" @checked(in_array($s->id, old('skills', $selected)))>{{ $s->name }}</label>@endforeach</div>
            @endforeach
        </div>
        <div class="card"><div class="field mb-0"><label for="ip_terms">IP & confidentiality <span class="muted">(optional)</span></label><textarea id="ip_terms" class="textarea" name="ip_terms" rows="3" placeholder="e.g. All code and designs transfer to the client on final payment. NDA required.">{{ old('ip_terms', $project->ip_terms) }}</textarea></div></div>
    </div>
    <aside class="sticky stack" style="--stack:16px">
        <div class="card">
            <h3>Budget</h3>
            <div class="field"><label>Pricing</label><div class="tabs-pill" style="width:100%">
                @foreach(['fixed' => 'Fixed price', 'hourly' => 'Hourly'] as $k => $v)<label style="flex:1;text-align:center;cursor:pointer" class="chip {{ old('contract_type', $project->contract_type) === $k ? 'chip-active' : '' }}"><input type="radio" name="contract_type" value="{{ $k }}" @checked(old('contract_type', $project->contract_type) === $k)>{{ $v }}</label>@endforeach</div></div>
            <div class="field"><label for="currency">Currency</label><select id="currency" class="select" name="currency">@foreach(\App\Support\Labels::CURRENCIES as $k => $v)<option value="{{ $k }}" @selected(old('currency', $project->currency) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="form-grid">
                <div class="field"><label for="budget_min">From</label><input id="budget_min" class="input" name="budget_min" inputmode="decimal" value="{{ old('budget_min', to_major($project->budget_min)) }}"></div>
                <div class="field"><label for="budget_max">To</label><input id="budget_max" x-ref="bmax" class="input" name="budget_max" inputmode="decimal" value="{{ old('budget_max', to_major($project->budget_max)) }}"></div>
            </div>
        </div>
        <div class="card">
            <div class="field"><label for="experience_level">Experience level</label><select id="experience_level" class="select" name="experience_level">@foreach(\App\Support\Labels::SENIORITY as $k => $v)<option value="{{ $k }}" @selected(old('experience_level', $project->experience_level) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="field"><label for="min_verified_level">Minimum verified level</label><select id="min_verified_level" class="select" name="min_verified_level"><option value="">No minimum</option>@foreach(\App\Support\Labels::LEVELS as $n => $l)<option value="{{ $n }}" @selected(old('min_verified_level', $project->min_verified_level) == $n)>{{ $n }} · {{ $l['name'] }}+</option>@endforeach</select></div>
            <div class="field"><label for="deadline">Deadline</label><input id="deadline" x-ref="deadline" type="date" class="input" name="deadline" value="{{ old('deadline', $project->deadline?->toDateString()) }}"></div>
            <div class="field"><label for="status">Status</label><select id="status" class="select" name="status">@foreach(['open' => 'Open for proposals', 'draft' => 'Draft', 'cancelled' => 'Cancelled'] as $k => $v)<option value="{{ $k }}" @selected(old('status', $project->status) === $k)>{{ $v }}</option>@endforeach</select></div>
            <button class="btn btn-accent btn-lg btn-block">{{ $project->exists ? 'Save' : 'Publish project' }}</button>
            <p class="hint mt-1 mb-0">A {{ setting('commission_bps') / 100 }}% marketplace fee applies to milestone payments ({{ setting('commission_payer') === 'client' ? 'added to what you pay' : (setting('commission_payer') === 'split' ? 'split between you and the professional' : 'deducted from the professional’s payout') }}).</p>
        </div>
    </aside>
</form>
@endsection
