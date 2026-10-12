@props(['level'])
@if($level)
    <span class="level-pill" title="{{ \App\Support\Labels::LEVELS[$level]['blurb'] ?? '' }}"><b>{{ $level }}</b> {{ level_name($level) }}</span>
@endif
