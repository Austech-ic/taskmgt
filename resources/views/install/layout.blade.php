@extends('layouts.base')
@section('title', 'Install')
@section('body')
<div class="install-shell">
    <div class="card card-pop">
        <div class="row-between mb-2">
            <a class="brand" href="#"><span class="brand-mark"><i data-lucide="zap"></i></span><span>Devhail</span></a>
            <span class="sticker">Installer ✦</span>
        </div>
        <div class="steps-bar">@for($i = 1; $i <= 3; $i++)<span @class(['on' => $i <= $step])></span>@endfor</div>
        @if($errors->any())
            <div class="alert alert-error mb-2"><i data-lucide="alert-circle"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
        @endif
        @yield('install')
    </div>
</div>
@endsection
