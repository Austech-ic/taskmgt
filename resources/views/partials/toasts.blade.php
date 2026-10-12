@php
    $toasts = collect(['success' => 'check-circle-2', 'error' => 'alert-circle', 'info' => 'info', 'warning' => 'alert-triangle'])
        ->filter(fn ($i, $k) => session()->has($k));
@endphp
@if($toasts->isNotEmpty())
    <div class="toasts" role="status" aria-live="polite">
        @foreach($toasts as $key => $icon)
            <div class="alert alert-{{ $key === 'warning' ? 'warn' : $key }}"><i data-lucide="{{ $icon }}"></i><div>{{ session($key) }}</div></div>
        @endforeach
    </div>
@endif
