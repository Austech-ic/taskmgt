@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<x-page-head title="Settings" subtitle="Everything here is editable without touching code." />
<div class="split-left">
    <nav class="card" style="padding:10px">
        @foreach($groups as $key => $g)
            <a href="{{ route('admin.settings', $key) }}" @class(['side-link', 'active' => $group === $key])><i data-lucide="{{ $g['icon'] }}"></i> {{ $g['title'] }}</a>
        @endforeach
    </nav>
    <div>
        <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="card">
            @csrf @method('PUT')
            <h2 style="font-size:1.5rem">{{ $groups[$group]['title'] }}</h2>
            @foreach($groups[$group]['fields'] as $key => $f)
                @php([$label, $type] = $f)
                @php($hint = isset($f[3]) ? str_replace('{webhook}', route('webhooks.paystack'), $f[3]) : null)
                @if($type === 'bool')
                    <label class="check mb-2"><input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key])> <span><strong>{{ $label }}</strong>@if($hint)<br><span class="hint">{{ $hint }}</span>@endif</span></label>
                @else
                    <div class="field">
                        <label for="{{ $key }}">{{ $label }}</label>
                        @if($type === 'textarea')
                            <textarea id="{{ $key }}" class="textarea" name="{{ $key }}" rows="3">{{ old($key, $values[$key]) }}</textarea>
                        @elseif(str_starts_with($type, 'select:'))
                            <select id="{{ $key }}" class="select" name="{{ $key }}">@foreach(explode(',', substr($type, 7)) as $opt)<option value="{{ $opt }}" @selected((string) $values[$key] === $opt)>{{ $opt }}</option>@endforeach</select>
                        @elseif($type === 'secret')
                            <input id="{{ $key }}" type="password" class="input" name="{{ $key }}" autocomplete="off" placeholder="{{ filled($values[$key]) ? '•••••••• (saved)' : 'Not set' }}">
                        @elseif($type === 'color')
                            <div class="row" style="flex-wrap:nowrap"><input type="color" value="{{ $values[$key] }}" oninput="this.nextElementSibling.value=this.value" style="width:52px;height:44px;border:0;background:none"><input id="{{ $key }}" class="input mono" name="{{ $key }}" value="{{ old($key, $values[$key]) }}"></div>
                        @else
                            <input id="{{ $key }}" type="{{ in_array($type, ['email', 'url', 'number']) ? $type : 'text' }}" class="input" name="{{ $key }}" value="{{ old($key, $values[$key]) }}">
                        @endif
                        @if($hint)<span class="hint">{{ $hint }}</span>@endif
                    </div>
                @endif
            @endforeach
            <div class="form-actions"><button class="btn btn-primary">Save {{ strtolower($groups[$group]['title']) }}</button></div>
        </form>
        @if($group === 'ai')
            <form method="POST" action="{{ route('admin.settings.ai.test') }}" class="card card-soft mt-2 row-between">@csrf<span class="small">Save first, then check the connection.</span><button class="btn ai-btn btn-sm"><i data-lucide="zap"></i> Test AI connection</button></form>
            <div class="card card-soft mt-2 small"><strong>What the AI does:</strong> profile bio drafts, smart talent search, job post and project scope writers, proposal drafts, blog drafts, assessment question drafts (saved inactive for human review) and the Hail help assistant. It never scores assessments, approves profiles or moves money.</div>
        @endif
        @if($group === 'payments')
            <div class="alert alert-warn mt-2"><i data-lucide="scale"></i><div>Before taking real milestone payments, confirm with your payment provider and a lawyer how client funds are held (escrow/collection) and whether your model needs extra licensing. Devhail only marks money as funded after the provider confirms it server-side.</div></div>
        @endif
    </div>
</div>
@endsection
