@props(['icon' => 'sparkles', 'title', 'text' => null])
<div class="empty">
    <div class="icon-tile lime"><i data-lucide="{{ $icon }}"></i></div>
    <h3>{{ $title }}</h3>
    @if($text)<p>{{ $text }}</p>@endif
    {{ $slot }}
</div>
