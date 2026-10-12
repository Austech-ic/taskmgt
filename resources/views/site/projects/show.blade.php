@extends('layouts.site')
@section('title', $project->title)
@section('description', \Illuminate\Support\Str::limit($project->description, 155))
@php($profile = auth()->user()?->profile)
@section('content')
<section class="section-tight">
    <div class="container split">
        <article class="stack" style="--stack:20px">
            <a href="{{ route('projects.index') }}" class="small link">← All projects</a>
            <div class="row gap-sm"><span class="badge badge-violet">{{ ucfirst($project->contract_type) }} price</span><x-status :status="$project->status" /></div>
            <h1 style="font-size:clamp(1.8rem,4vw,2.8rem)" class="mb-0">{{ $project->title }}</h1>
            <div class="muted">Posted by {{ $project->clientName() }} · {{ $project->created_at->diffForHumans() }} · {{ $project->proposals_count }} proposals</div>
            <div class="card prose">{!! nl2br(e($project->description)) !!}</div>
            <div class="grid-2">
                @if($project->deliverables)<div class="card"><h3>Deliverables</h3><ul class="small ink-2" style="padding-left:1.1em;margin:0">@foreach(preg_split('/\r?\n/', trim($project->deliverables)) as $l)@if(trim($l))<li>{{ ltrim(trim($l), '-•* ') }}</li>@endif @endforeach</ul></div>@endif
                @if($project->acceptance_criteria)<div class="card"><h3>Acceptance criteria</h3><ul class="small ink-2" style="padding-left:1.1em;margin:0">@foreach(preg_split('/\r?\n/', trim($project->acceptance_criteria)) as $l)@if(trim($l))<li>{{ ltrim(trim($l), '-•* ') }}</li>@endif @endforeach</ul></div>@endif
            </div>
            @if($project->ip_terms)<div class="card card-soft"><h4>IP & confidentiality</h4><p class="small mb-0">{{ $project->ip_terms }}</p></div>@endif

            @if($project->status === 'open' && $profile && auth()->id() !== $project->client_id)
                <div class="card card-pop" id="propose">
                    @if($proposal)
                        <div class="alert alert-success"><i data-lucide="check-circle-2"></i><div>You pitched {{ money($proposal->amount_minor, $project->currency) }} · status <strong>{{ ucfirst($proposal->status) }}</strong>. <a class="link" href="{{ route('app.proposals.index') }}">Manage proposals</a></div></div>
                    @else
                        <div class="row-between"><h3 class="mb-0">Send a proposal</h3><span class="small muted">{{ setting('commission_bps') / 100 }}% commission applies on payouts</span></div>
                        <form method="POST" action="{{ route('app.projects.propose', $project) }}" class="mt-2"
                              x-data="aiAssist('proposal', function () { return { project_id: {{ $project->id }}, notes: this.$refs.cover.value }; }, function (r) { this.$refs.cover.value = r; })">
                            @csrf
                            <div class="field">
                                <div class="row-between"><label for="cover">Cover letter</label>
                                    <button type="button" class="btn btn-sm ai-btn" @click="run()" :disabled="loading"><i data-lucide="sparkles"></i><span x-text="loading ? 'Drafting…' : 'Draft with AI'"></span></button></div>
                                <textarea id="cover" x-ref="cover" class="textarea" name="cover_letter" rows="7" required maxlength="5000" placeholder="Show you get the problem, then how you’ll solve it."></textarea>
                                <span class="error" x-show="error" x-text="error"></span>
                            </div>
                            <div class="field"><label for="approach">Approach & assumptions</label><textarea id="approach" class="textarea" name="approach" rows="3" maxlength="3000" placeholder="Stack, steps, what you need from the client, what’s out of scope."></textarea></div>
                            <div x-data="milestones([])">
                                <label class="label">Milestones</label>
                                <template x-for="(row, i) in rows" :key="i">
                                    <div class="row mt-1" style="flex-wrap:nowrap">
                                        <input class="input" :name="`milestones[${i}][title]`" x-model="row.title" placeholder="e.g. Design + homepage" required>
                                        <input class="input" style="max-width:150px" :name="`milestones[${i}][amount]`" x-model="row.amount" placeholder="₦ amount" inputmode="decimal" required>
                                        <input class="input" style="max-width:90px" type="number" min="1" :name="`milestones[${i}][days]`" placeholder="days">
                                        <button type="button" class="btn btn-icon btn-ghost" @click="remove(i)" aria-label="Remove milestone"><i data-lucide="x"></i></button>
                                    </div>
                                </template>
                                <div class="row-between mt-1"><button type="button" class="btn btn-sm" @click="add()"><i data-lucide="plus"></i> Add milestone</button><strong class="money">Total ₦<span x-text="fmt(sum)"></span></strong></div>
                            </div>
                            <div class="field mt-2"><label for="days">Estimated duration (days)</label><input id="days" type="number" min="1" max="365" class="input" name="duration_days" value="14" required></div>
                            <button class="btn btn-accent btn-lg btn-block" type="submit">Send proposal</button>
                        </form>
                    @endif
                </div>
            @endif
        </article>
        <aside class="sticky stack" style="--stack:16px">
            <div class="card">
                <div class="small muted">Budget</div>
                <div style="font:800 1.7rem var(--display);letter-spacing:-.03em" class="money">{{ $project->budgetLabel() }}</div>
                <div class="divider"></div>
                <div class="list-divided small">
                    <div class="row-between"><span class="muted">Experience</span><strong>{{ \App\Support\Labels::SENIORITY[$project->experience_level] ?? ucfirst($project->experience_level) }}</strong></div>
                    @if($project->min_verified_level)<div class="row-between"><span class="muted">Min. verified level</span><x-level :level="$project->min_verified_level" /></div>@endif
                    @if($project->deadline)<div class="row-between"><span class="muted">Deadline</span><strong>{{ $project->deadline->format('j M Y') }}</strong></div>@endif
                </div>
                <div class="chips mt-2">@foreach($project->skills as $s)<span class="chip">{{ $s->name }}</span>@endforeach</div>
                @guest<a href="{{ route('register') }}" class="btn btn-accent btn-block mt-2">Join free to pitch</a>@endguest
                @auth @if(! $profile && auth()->id() !== $project->client_id)<a href="{{ route('app.onboarding', ['want' => 'talent']) }}" class="btn btn-accent btn-block mt-2">Create a talent profile to pitch</a>@endif @endauth
                @if(auth()->id() === $project->client_id)<a href="{{ route('app.projects.proposals', $project) }}" class="btn btn-primary btn-block mt-2">Review proposals</a>@endif
            </div>
            <div class="card card-soft small">
                <strong>🛡️ How you’re protected</strong>
                <p class="mt-1 mb-0 muted">Milestones are funded through a licensed payment partner before work starts. Approval releases payment; disputes pause it for neutral review.</p>
            </div>
        </aside>
    </div>
</section>
@endsection
