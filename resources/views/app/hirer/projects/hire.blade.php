@extends('layouts.app')
@section('title', 'Hire '.$proposal->profile->user->name)
@section('content')
<a href="{{ route('app.projects.proposals', $project) }}" class="small link">← Proposals</a>
<x-page-head :title="'Contract with '.$proposal->profile->user->name" subtitle="Lock scope, milestones and acceptance criteria. They’ll review and accept before any money moves." />
<form method="POST" action="{{ route('app.projects.hire.store', [$project, $proposal]) }}" class="split">
    @csrf
    <div class="stack" style="--stack:20px">
        <div class="card">
            <div class="field"><label for="scope">Scope of work</label><textarea id="scope" class="textarea" name="scope" rows="7" required>{{ old('scope', $project->description."\n\n".$proposal->approach) }}</textarea></div>
            <div class="field mb-0"><label for="acceptance_criteria">Acceptance criteria</label><textarea id="acceptance_criteria" class="textarea" name="acceptance_criteria" rows="5" required>{{ old('acceptance_criteria', $project->acceptance_criteria) }}</textarea><span class="hint">Deliverables are reviewed against these. Make them testable.</span></div>
        </div>
        @php($initial = collect(old('milestones', collect($proposal->milestones ?? [])->map(fn ($m) => ['title' => $m['title'], 'amount' => to_major($m['amount_minor']), 'due_on' => ! empty($m['days']) ? now()->addDays((int) $m['days'])->toDateString() : '', 'description' => ''])->all()))->values())
        <div class="card" x-data="milestones({{ json_encode($initial) }})">
            <div class="card-head"><h3>Milestones</h3><strong class="money">Total {{ $project->currency === 'USD' ? '$' : '₦' }}<span x-text="fmt(sum)"></span></strong></div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="card card-soft mb-2">
                    <div class="row-between mb-1"><strong x-text="'Milestone ' + (i + 1)"></strong><button type="button" class="btn btn-ghost btn-sm btn-icon" @click="remove(i)" aria-label="Remove"><i data-lucide="x"></i></button></div>
                    <div class="form-grid">
                        <div class="field span-2"><input class="input" :name="`milestones[${i}][title]`" x-model="row.title" placeholder="Title" required></div>
                        <div class="field"><input class="input" :name="`milestones[${i}][amount]`" x-model="row.amount" placeholder="Amount" inputmode="decimal" required></div>
                        <div class="field"><input class="input" type="date" :name="`milestones[${i}][due_on]`" x-model="row.due_on"></div>
                        <div class="field span-2 mb-0"><input class="input" :name="`milestones[${i}][description]`" x-model="row.description" placeholder="What’s delivered (optional)"></div>
                    </div>
                </div>
            </template>
            <button type="button" class="btn btn-sm" @click="add()"><i data-lucide="plus"></i> Add milestone</button>
        </div>
    </div>
    <aside class="sticky">
        <div class="card card-pop">
            <div class="row"><img class="avatar avatar-lg" src="{{ $proposal->profile->user->avatarUrl() }}" alt=""><div><strong>{{ $proposal->profile->user->name }}</strong><div class="small muted">{{ $proposal->profile->primary_role }}</div></div></div>
            <div class="divider"></div>
            <ul class="small" style="padding-left:1.1em;margin:0 0 14px">
                <li>Marketplace fee: <strong>{{ setting('commission_bps') / 100 }}%</strong> of each milestone ({{ setting('commission_payer') === 'client' ? 'added to your payment' : (setting('commission_payer') === 'split' ? 'split 50/50' : 'deducted from the pro’s payout') }}).</li>
                <li>You fund one milestone at a time via our licensed payment partner.</li>
                <li>Up to {{ setting('max_revisions') }} revision requests per milestone; disputes go to a neutral officer.</li>
            </ul>
            <label class="check mb-2"><input type="checkbox" name="agree" value="1" required> I agree to these contract terms and the <a class="link" href="{{ route('pages.show', 'fees') }}" target="_blank">fees & payments policy</a>.</label>
            <button class="btn btn-accent btn-lg btn-block">Send contract</button>
        </div>
    </aside>
</form>
@endsection
