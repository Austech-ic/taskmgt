@extends('install.layout', ['step' => 3])
@section('install')
    <h2>Name it & make it yours</h2>
    <p class="muted">You can change everything later in Admin → Settings.</p>
    <form method="POST" action="{{ route('install.finish') }}" class="mt-2">
        @csrf
        <div class="form-grid">
            <div class="field"><label for="site_name">Site name</label><input class="input" id="site_name" name="site_name" value="{{ old('site_name', 'Devhail') }}" required></div>
            <div class="field"><label for="contact_email">Public contact email</label><input class="input" type="email" id="contact_email" name="contact_email" value="{{ old('contact_email') }}" required></div>
            <div class="field span-2"><label for="app_url">Site URL</label><input class="input" type="url" id="app_url" name="app_url" value="{{ old('app_url', $url) }}" required></div>
        </div>
        <h3 class="mt-2">Your admin account</h3>
        <div class="form-grid">
            <div class="field"><label for="admin_name">Full name</label><input class="input" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required></div>
            <div class="field"><label for="admin_email">Email</label><input class="input" type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required></div>
            <div class="field"><label for="admin_password">Password</label><input class="input" type="password" id="admin_password" name="admin_password" minlength="8" required></div>
            <div class="field"><label for="admin_password_confirmation">Confirm password</label><input class="input" type="password" id="admin_password_confirmation" name="admin_password_confirmation" required></div>
        </div>
        <details class="card card-soft mb-2"><summary class="strong" style="cursor:pointer">Optional: email (SMTP) & AI</summary>
            <p class="small muted mt-1">Add SMTP so people get verification and notification emails. Leave blank to keep notifications in-app only.</p>
            <div class="form-grid">
                <div class="field"><label for="mail_host">SMTP host</label><input class="input" id="mail_host" name="mail_host" value="{{ old('mail_host') }}" placeholder="mail.yourdomain.com"></div>
                <div class="field"><label for="mail_port">Port</label><input class="input" id="mail_port" name="mail_port" value="{{ old('mail_port', 587) }}"></div>
                <div class="field"><label for="mail_username">Username</label><input class="input" id="mail_username" name="mail_username" value="{{ old('mail_username') }}"></div>
                <div class="field"><label for="mail_password">Password</label><input class="input" type="password" id="mail_password" name="mail_password"></div>
                <div class="field span-2"><label for="mail_from">From address</label><input class="input" type="email" id="mail_from" name="mail_from" value="{{ old('mail_from') }}"></div>
                <div class="field span-2"><label for="ai_api_key">Anthropic API key (powers the AI features)</label><input class="input" id="ai_api_key" name="ai_api_key" placeholder="sk-ant-…" autocomplete="off"><span class="hint">Stored encrypted. You can add it later in Admin → Settings → AI.</span></div>
            </div>
        </details>
        <label class="check mb-2"><input type="checkbox" name="demo" value="1" @checked(old('demo', true))> Add demo talent, jobs and projects so the site doesn’t look empty (easy to delete later)</label>
        <div class="form-actions"><a class="btn btn-ghost" href="{{ route('install.database') }}">Back</a><button class="btn btn-accent btn-lg" type="submit">Install Devhail ✦</button></div>
    </form>
@endsection
