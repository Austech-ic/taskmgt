@extends('layouts.admin')
@section('title', ($item->exists ? 'Edit ' : 'New ').$def['label'])
@section('content')
<a href="{{ route('admin.resources.index', $def['key']) }}" class="small link">← {{ $def['plural'] }}</a>
<x-page-head :title="($item->exists ? 'Edit ' : 'New ').strtolower($def['label'])">
    @if($item->exists && isset($def['view']))<a class="btn btn-sm" href="{{ ($def['view'])($item) }}" target="_blank"><i data-lucide="external-link"></i> View</a>@endif
</x-page-head>
<div class="split">
    <form method="POST" action="{{ $item->exists ? route('admin.resources.update', [$def['key'], $item->id]) : route('admin.resources.store', $def['key']) }}" enctype="multipart/form-data" class="card" id="resource-form">
        @csrf @if($item->exists) @method('PUT') @endif
        @foreach($def['fields'] as $key => $f)
            @php([$label, $type] = $f)
            @php($value = old($key, $item->$key))
            @if($type === 'bool')
                <label class="check mb-2"><input type="checkbox" name="{{ $key }}" value="1" @checked($value)> {{ $label }}</label>
            @else
                <div class="field"><label for="{{ $key }}">{{ $label }}</label>
                    @switch($type)
                        @case('textarea')<textarea id="{{ $key }}" class="textarea" name="{{ $key }}" rows="4">{{ $value }}</textarea>@break
                        @case('markdown')<textarea id="{{ $key }}" class="textarea mono" name="{{ $key }}" rows="18" style="font-size:.88rem">{{ $value }}</textarea><span class="hint">Markdown: ## heading, **bold**, - list, [link](https://…), | tables |</span>@break
                        @case('lines')<textarea id="{{ $key }}" class="textarea" name="{{ $key }}" rows="5">{{ is_array($value) ? implode("\n", $value) : $value }}</textarea>@break
                        @case('select')<select id="{{ $key }}" class="select" name="{{ $key }}">@foreach($options[$key] as $ok => $ov)<option value="{{ $ok }}" @selected((string) $value === (string) $ok)>{{ $ov }}</option>@endforeach</select>@break
                        @case('number')<input id="{{ $key }}" type="number" class="input" name="{{ $key }}" value="{{ $value }}">@break
                        @case('datetime')<input id="{{ $key }}" type="datetime-local" class="input" name="{{ $key }}" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}">@break
                        @case('image')@if($item->$key)<img src="{{ Storage::disk('public')->url($item->$key) }}" alt="" style="max-width:240px;border-radius:12px;margin-bottom:8px">@endif<input id="{{ $key }}" type="file" class="input" name="{{ $key }}" accept="image/*">@break
                        @case('slug')<input id="{{ $key }}" class="input mono" name="{{ $key }}" value="{{ $value }}" placeholder="auto from title">@break
                        @default<input id="{{ $key }}" class="input" name="{{ $key }}" value="{{ $value }}">
                    @endswitch
                </div>
            @endif
        @endforeach
        <div class="form-actions"><button class="btn btn-primary">Save</button></div>
    </form>
    <aside class="stack sticky" style="--stack:16px">
        @if(! empty($def['ai']))
            <div class="ai-panel" x-data="{ loading: false, error: '', async draft() { this.loading = true; this.error = ''; try { const d = await postJson('{{ route('admin.ai.blog') }}', { topic: this.$refs.topic.value, audience: this.$refs.aud.value }); const b = document.getElementById('body'); b.value = d.result; const t = document.getElementById('title'); if (!t.value) t.value = this.$refs.topic.value; } catch (e) { this.error = e.message; } finally { this.loading = false; } } }">
                <div class="row mb-1"><span class="sparkle"><i data-lucide="sparkles"></i></span><strong>AI blog writer</strong></div>
                <div class="field"><input class="input" x-ref="topic" placeholder="Topic: e.g. How to ace a React assessment"></div>
                <div class="field"><select class="select" x-ref="aud"><option>Tech professionals</option><option>Hiring managers & founders</option><option>Everyone</option></select></div>
                <button type="button" class="btn ai-btn btn-block" @click="draft()" :disabled="loading"><span x-text="loading ? 'Writing… (up to ~30s)' : 'Draft post body'"></span></button>
                <p class="error small mt-1 mb-0" x-show="error" x-text="error"></p>
                <p class="hint mt-1 mb-0">Always fact-check and edit before publishing.</p>
            </div>
        @endif
        @if($item->exists)
            <form method="POST" action="{{ route('admin.resources.destroy', [$def['key'], $item->id]) }}" data-confirm="Delete permanently?" class="card card-soft">@csrf @method('DELETE')<button class="btn btn-danger btn-block"><i data-lucide="trash-2"></i> Delete {{ strtolower($def['label']) }}</button></form>
        @endif
    </aside>
</div>
@endsection
