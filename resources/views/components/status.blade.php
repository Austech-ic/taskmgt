@props(['status', 'label' => null])
<span {{ $attributes->merge(['class' => 'badge badge-'.\App\Support\Labels::tone((string) $status)]) }}>{{ $label ?? \Illuminate\Support\Str::headline((string) $status) }}</span>
