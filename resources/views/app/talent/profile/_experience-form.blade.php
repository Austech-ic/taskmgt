<form method="POST" action="{{ route('app.profile.experience.save') }}" x-data="{ current: {{ $exp?->is_current ? 'true' : 'false' }} }">
    @csrf
    @if($exp)<input type="hidden" name="id" value="{{ $exp->id }}">@endif
    <div class="field"><label>Type</label><select name="kind" class="select"><option value="work" @selected($exp?->kind !== 'education')>Work</option><option value="education" @selected($exp?->kind === 'education')>Education</option></select></div>
    <div class="field"><label>Title / degree</label><input class="input" name="title" value="{{ $exp?->title }}" required></div>
    <div class="field"><label>Company / school</label><input class="input" name="organization" value="{{ $exp?->organization }}" required></div>
    <div class="field"><label>Location</label><input class="input" name="location" value="{{ $exp?->location }}"></div>
    <div class="form-grid">
        <div class="field"><label>Start</label><input type="date" class="input" name="started_on" value="{{ $exp?->started_on?->toDateString() }}"></div>
        <div class="field" x-show="!current"><label>End</label><input type="date" class="input" name="ended_on" value="{{ $exp?->ended_on?->toDateString() }}"></div>
    </div>
    <label class="check mb-2"><input type="checkbox" name="is_current" value="1" x-model="current"> I’m here now</label>
    <div class="field"><label>What did you do / ship?</label><textarea class="textarea" name="description" rows="3">{{ $exp?->description }}</textarea></div>
    <button class="btn btn-primary btn-block">{{ $exp ? 'Update' : 'Add' }}</button>
</form>
