<div class="row-between mb-3">
    <div class="tabs-pill">
        <a href="{{ route('app.profile.edit') }}" @class(['is-active' => request()->routeIs('app.profile.edit')])>Basics</a>
        <a href="{{ route('app.profile.skills') }}" @class(['is-active' => request()->routeIs('app.profile.skills')])>Skills</a>
        <a href="{{ route('app.profile.experience') }}" @class(['is-active' => request()->routeIs('app.profile.experience')])>Experience</a>
        <a href="{{ route('app.profile.portfolio') }}" @class(['is-active' => request()->routeIs('app.profile.portfolio')])>Portfolio</a>
    </div>
    <a class="btn btn-sm" href="{{ route('talent.show', auth()->user()->profile) }}" target="_blank"><i data-lucide="eye"></i> Preview</a>
</div>
