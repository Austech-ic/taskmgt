@extends('install.layout', ['step' => 2])
@section('install')
    <h2>Connect your database</h2>
    <p class="muted">On shared hosting, create a MySQL database and user in cPanel → “MySQL Databases”, then paste the details here.</p>
    <form method="POST" action="{{ route('install.database.save') }}" x-data="{ driver: '{{ old('driver', $mysql ? 'mysql' : 'sqlite') }}' }" class="mt-2">
        @csrf
        <div class="radio-cards mb-2">
            <label class="radio-card"><input type="radio" name="driver" value="mysql" x-model="driver" @disabled(! $mysql)><div class="card"><strong>MySQL / MariaDB</strong><div class="small muted">Recommended for production</div></div></label>
            <label class="radio-card"><input type="radio" name="driver" value="sqlite" x-model="driver" @disabled(! $sqlite)><div class="card"><strong>SQLite</strong><div class="small muted">Zero setup — great for demos</div></div></label>
        </div>
        <div x-show="driver === 'mysql'" class="form-grid">
            <div class="field"><label for="host">Host</label><input class="input" id="host" name="host" value="{{ old('host', 'localhost') }}"></div>
            <div class="field"><label for="port">Port</label><input class="input" id="port" name="port" value="{{ old('port', 3306) }}"></div>
            <div class="field span-2"><label for="database">Database name</label><input class="input" id="database" name="database" value="{{ old('database') }}" placeholder="cpaneluser_devhail"></div>
            <div class="field"><label for="username">Username</label><input class="input" id="username" name="username" value="{{ old('username') }}"></div>
            <div class="field"><label for="password">Password</label><input class="input" id="password" type="password" name="password"></div>
        </div>
        <div class="form-actions"><a class="btn btn-ghost" href="{{ route('install.welcome') }}">Back</a><button class="btn btn-primary btn-lg" type="submit">Test & continue <i data-lucide="arrow-right"></i></button></div>
    </form>
@endsection
