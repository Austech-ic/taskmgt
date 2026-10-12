@extends('layouts.base')
@section('title', $assessment->title)
@section('body')
<div x-data="exam({ total: {{ $questions->count() }}, seconds: {{ $attempt->secondsLeft() }}, answers: {{ json_encode((object) ($attempt->answers ?? [])) }}, focusLosses: {{ $attempt->focus_losses }}, saveUrl: '{{ route('app.attempts.save', $attempt) }}' })">
    <div class="exam-bar">
        <div class="container inner">
            <span class="brand-mark"><i data-lucide="zap"></i></span>
            <div class="grow"><strong>{{ $assessment->title }}</strong><div class="small" style="opacity:.7"><span x-text="answeredCount"></span>/{{ $questions->count() }} answered · <span x-show="saving">saving…</span><span x-show="!saving && savedAt">saved ✓</span></div></div>
            <span class="timer" :class="{ low: seconds < 120 }" x-text="clock" aria-live="polite"></span>
            <button class="btn btn-accent btn-sm" @click="submit(false)">Submit</button>
        </div>
    </div>
    <div class="container section-tight">
        <div class="split">
            <div>
                @foreach($questions as $i => $q)
                    <section class="card" x-show="current === {{ $i }}" @if($i) x-cloak @endif>
                        <div class="row-between mb-2"><span class="badge badge-violet">Question {{ $i + 1 }} of {{ $questions->count() }}</span><span class="small muted">{{ $q->points }} {{ \Illuminate\Support\Str::plural('pt', $q->points) }}</span></div>
                        <h2 style="font-size:1.35rem;line-height:1.35">{{ $q->body }}</h2>
                        @if($q->code_snippet)<pre><code>{{ $q->code_snippet }}</code></pre>@endif
                        <div class="stack mt-2" style="--stack:10px">
                            @if($q->type === 'short')
                                <input class="input input-lg" x-model="answers[{{ $q->id }}]" placeholder="Your answer" maxlength="500">
                            @else
                                @foreach($q->options as $opt)
                                    <label class="option" :class="{ selected: {{ $q->type === 'multiple' ? "(answers[{$q->id}] || []).includes('{$opt['key']}')" : "answers[{$q->id}] === '{$opt['key']}'" }} }">
                                        @if($q->type === 'multiple')
                                            <input type="checkbox" value="{{ $opt['key'] }}" x-model="answers[{{ $q->id }}]" x-init="if (!Array.isArray(answers[{{ $q->id }}])) answers[{{ $q->id }}] = []">
                                        @else
                                            <input type="radio" name="q{{ $q->id }}" value="{{ $opt['key'] }}" x-model="answers[{{ $q->id }}]">
                                        @endif
                                        <span class="key">{{ $opt['key'] }}</span><span>{{ $opt['text'] }}</span>
                                    </label>
                                @endforeach
                            @endif
                        </div>
                        <div class="row-between mt-3">
                            <button class="btn" @click="go(current - 1)" :disabled="current === 0"><i data-lucide="arrow-left"></i> Back</button>
                            @if($i < $questions->count() - 1)
                                <button class="btn btn-primary" @click="go(current + 1); save()">Next <i data-lucide="arrow-right"></i></button>
                            @else
                                <button class="btn btn-accent" @click="go(current + 1)">Review & submit</button>
                            @endif
                        </div>
                    </section>
                @endforeach

                <section class="card card-pop" x-show="current >= total" x-cloak>
                    <h2>Ready to submit? 🏁</h2>
                    <p class="muted">You’ve answered <strong x-text="answeredCount"></strong> of {{ $questions->count() }}. You can jump back to any question from the grid.</p>
                    <form method="POST" action="{{ route('app.attempts.submit', $attempt) }}" x-ref="form" @submit="submitting = true">
                        @csrf
                        <input type="hidden" name="answers" x-ref="answers">
                        <input type="hidden" name="focus_losses" x-ref="focus">
                        @if($assessment->requires_review && $assessment->practical_brief)
                            <div class="card card-soft mb-2">
                                <h3>Practical task</h3>
                                <div class="prose" style="font-size:.95rem">{!! nl2br(e($assessment->practical_brief)) !!}</div>
                                <div class="field mt-2"><label for="practical_url">Link to your work (Figma, repo, prototype)</label><input id="practical_url" type="url" class="input" name="practical_url" placeholder="https://"></div>
                                <div class="field"><label for="practical_submission">Your rationale</label><textarea id="practical_submission" class="textarea" name="practical_submission" rows="7" maxlength="10000"></textarea></div>
                            </div>
                        @endif
                        <button type="button" class="btn btn-accent btn-lg" @click="submit(false)">Submit my answers</button>
                    </form>
                </section>
            </div>
            <aside class="sticky">
                <div class="card">
                    <h4>Questions</h4>
                    <div class="q-nav">
                        @foreach($questions as $i => $q)
                            <button type="button" @click="go({{ $i }})" :class="{ current: current === {{ $i }}, answered: isAnswered({{ $q->id }}) }">{{ $i + 1 }}</button>
                        @endforeach
                    </div>
                    <div class="divider"></div>
                    <p class="small muted mb-0">Answers autosave every 20 seconds. Leaving this tab is logged; frequent switching sends your attempt to a human reviewer — it never auto-fails you.</p>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
