@extends('install.layout', ['step' => 1])
@section('install')
    <h2>Let’s get you live 🚀</h2>
    <p class="muted">Three quick steps: check your server, connect a database, create your admin account. No terminal needed.</p>
    <div class="list-divided mt-2">
        @foreach($checks as $label => $pass)
            <div class="row-between"><span>{{ $label }}</span>
                @if($pass)<span class="badge badge-green"><i data-lucide="check"></i> Good</span>@else<span class="badge badge-red"><i data-lucide="x"></i> Fix this</span>@endif
            </div>
        @endforeach
    </div>
    @if($ok)
        <div class="form-actions mt-2"><a class="btn btn-primary btn-lg" href="{{ route('install.database') }}">Continue <i data-lucide="arrow-right"></i></a></div>
    @else
        <div class="alert alert-warn mt-2"><i data-lucide="alert-triangle"></i><div>Fix the items marked red (your host’s control panel usually has a “PHP selector” for version and extensions, and a file manager for folder permissions — 775 for folders), then refresh this page.</div></div>
    @endif
@endsection
