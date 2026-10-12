@extends('layouts.admin')
@section('title', $def['plural'])
@section('content')
<x-page-head :title="$def['plural']" :subtitle="$def['intro'] ?? null"><a class="btn btn-primary" href="{{ route('admin.resources.create', $def['key']) }}"><i data-lucide="plus"></i> New {{ strtolower($def['label']) }}</a></x-page-head>
<form class="mb-3"><input class="input" style="max-width:320px" name="q" value="{{ request('q') }}" placeholder="Search…"></form>
@if($items->isEmpty())
    <x-empty :icon="$def['icon']" :title="'No '.strtolower($def['plural']).' yet'" />
@else
<div class="table-wrap"><table class="table"><thead><tr>@foreach($def['columns'] as $col => $label)<th>{{ $label }}</th>@endforeach<th></th></tr></thead><tbody>
@foreach($items as $item)
    <tr>@foreach($def['columns'] as $col => $label)
        @php($v = data_get($item, $col))
        <td>@if(is_bool($v))<span class="badge {{ $v ? 'badge-green' : '' }}">{{ $v ? 'Yes' : 'No' }}</span>@elseif($v instanceof \Carbon\CarbonInterface){{ $v->format('j M Y') }}@else{{ \Illuminate\Support\Str::limit((string) $v, 70) }}@endif</td>
    @endforeach
        <td class="actions">@isset($def['view'])<a class="btn btn-sm btn-ghost" href="{{ ($def['view'])($item) }}" target="_blank" aria-label="View"><i data-lucide="external-link"></i></a>@endisset<a class="btn btn-sm" href="{{ route('admin.resources.edit', [$def['key'], $item->id]) }}">Edit</a></td></tr>
@endforeach
</tbody></table></div>
{{ $items->links() }}
@endif
@endsection
