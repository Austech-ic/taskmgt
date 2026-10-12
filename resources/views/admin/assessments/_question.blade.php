<form method="POST" action="{{ $action }}" x-data="{ type: '{{ $q?->type ?? 'single' }}' }">
    @csrf @if($method === 'PUT') @method('PUT') @endif
    <div class="form-grid">
        <div class="field"><label>Type</label><select class="select" name="type" x-model="type"><option value="single">Single choice</option><option value="multiple">Multiple choice</option><option value="short">Short answer</option></select></div>
        <div class="field"><label>Topic</label><input class="input" name="topic" value="{{ $q?->topic }}"></div>
        <div class="field span-2"><label>Question</label><textarea class="textarea" name="body" rows="2" required>{{ $q?->body }}</textarea></div>
        <div class="field span-2"><label>Code snippet (optional)</label><textarea class="textarea mono" name="code_snippet" rows="3">{{ $q?->code_snippet }}</textarea></div>
    </div>
    @php($opts = collect($q?->options ?? [])->pluck('text', 'key'))
    <div x-show="type !== 'short'">
        @foreach(['a', 'b', 'c', 'd', 'e'] as $k)
            <div class="row mb-1" style="flex-wrap:nowrap"><label class="check" style="min-width:64px"><input :type="type === 'single' ? 'radio' : 'checkbox'" name="correct[]" value="{{ $k }}" @checked(in_array($k, $q?->correct ?? []))> {{ strtoupper($k) }}</label><input class="input" name="options[{{ $k }}]" value="{{ $opts[$k] ?? '' }}" placeholder="Option {{ strtoupper($k) }}{{ $k === 'e' ? ' (optional)' : '' }}"></div>
        @endforeach
        <span class="hint">Tick the correct option(s).</span>
    </div>
    <div x-show="type === 'short'" x-cloak class="field"><label>Accepted answers (one per field)</label>
        @for($i = 0; $i < 3; $i++)<input class="input mb-1" :name="type === 'short' ? 'correct[]' : ''" value="{{ $q?->type === 'short' ? ($q->correct[$i] ?? '') : '' }}">@endfor</div>
    <div class="form-grid mt-1">
        <div class="field"><label>Points</label><input type="number" class="input" name="points" value="{{ $q?->points ?? 1 }}" min="1" max="10"></div>
        <div class="field"><label>Difficulty</label><input type="number" class="input" name="difficulty" value="{{ $q?->difficulty ?? 2 }}" min="1" max="5"></div>
        <div class="field span-2"><label>Explanation (reviewers only)</label><input class="input" name="explanation" value="{{ $q?->explanation }}"></div>
    </div>
    <label class="check mb-2"><input type="checkbox" name="is_active" value="1" @checked($q ? $q->is_active : true)> Active (served to candidates)</label>
    <button class="btn btn-sm btn-primary">{{ $q ? 'Save question' : 'Add question' }}</button>
</form>
