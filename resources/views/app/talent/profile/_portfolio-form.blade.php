<form method="POST" action="{{ route('app.profile.portfolio.save') }}" enctype="multipart/form-data">
    @csrf
    @if($item)<input type="hidden" name="id" value="{{ $item->id }}">@endif
    <div class="field"><label>Project name</label><input class="input" name="title" value="{{ $item?->title }}" required></div>
    <div class="field"><label>Your role</label><input class="input" name="role" value="{{ $item?->role }}" placeholder="Lead frontend engineer"></div>
    <div class="field"><label>What is it?</label><textarea class="textarea" name="description" rows="3" required>{{ $item?->description }}</textarea></div>
    <div class="field"><label>Stack</label><input class="input" name="technologies" value="{{ $item?->technologies }}" placeholder="React, Laravel, MySQL"></div>
    <div class="field"><label>Outcome</label><input class="input" name="outcome" value="{{ $item?->outcome }}" placeholder="Cut checkout time by 30%"></div>
    <div class="field"><label>Live URL</label><input type="url" class="input" name="url" value="{{ $item?->url }}"></div>
    <div class="field"><label>Repo URL</label><input type="url" class="input" name="repo_url" value="{{ $item?->repo_url }}"></div>
    <div class="field"><label>Cover image</label><input type="file" class="input" name="image" accept="image/*"></div>
    <button class="btn btn-primary btn-block">{{ $item ? 'Update' : 'Add project' }}</button>
</form>
