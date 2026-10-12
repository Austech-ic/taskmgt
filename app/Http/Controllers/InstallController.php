<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EnvWriter;
use App\Support\Settings;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Three-step web installer so Devhail can go live on shared hosting
 * with nothing but a file upload: requirements → database → site & admin.
 */
class InstallController extends Controller
{
    public function welcome()
    {
        $checks = [
            'PHP 8.2 or newer (you have '.PHP_VERSION.')' => version_compare(PHP_VERSION, '8.2.0', '>='),
        ];
        foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'curl'] as $ext) {
            $checks["PHP extension: {$ext}"] = extension_loaded($ext);
        }
        $checks['PDO driver: MySQL or SQLite'] = extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite');
        foreach (['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $checks["Writable: /{$dir}"] = is_writable(base_path($dir));
        }
        $checks['Writable: .env file'] = file_exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path());

        return view('install.welcome', ['checks' => $checks, 'ok' => ! in_array(false, $checks, true)]);
    }

    public function database()
    {
        return view('install.database', [
            'sqlite' => extension_loaded('pdo_sqlite'),
            'mysql' => extension_loaded('pdo_mysql'),
        ]);
    }

    public function saveDatabase(Request $request)
    {
        $data = $request->validate([
            'driver' => 'required|in:mysql,sqlite',
            'host' => 'required_if:driver,mysql|nullable|string|max:255',
            'port' => 'required_if:driver,mysql|nullable|integer',
            'database' => 'required_if:driver,mysql|nullable|string|max:255',
            'username' => 'required_if:driver,mysql|nullable|string|max:255',
            'password' => 'nullable|string|max:255',
        ]);

        try {
            $this->applyDatabase($data);
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['database' => 'Couldn’t connect: '.$e->getMessage()]);
        }

        EnvWriter::set($data['driver'] === 'mysql' ? [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['host'],
            'DB_PORT' => $data['port'],
            'DB_DATABASE' => $data['database'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => $data['password'] ?? '',
        ] : ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => '']);

        session(['install.db' => $data]);

        return redirect()->route('install.site');
    }

    public function site()
    {
        if (! session('install.db')) {
            return redirect()->route('install.database');
        }

        return view('install.site', ['url' => rtrim(request()->root(), '/')]);
    }

    public function finish(Request $request)
    {
        $db = session('install.db');
        if (! $db) {
            return redirect()->route('install.database');
        }

        $data = $request->validate([
            'site_name' => 'required|string|max:60',
            'app_url' => 'required|url',
            'contact_email' => 'required|email',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:8|confirmed',
            'demo' => 'nullable|boolean',
            'ai_api_key' => 'nullable|string|max:300',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from' => 'nullable|email',
        ]);

        try {
            @set_time_limit(300);
            $this->applyDatabase($db);
            Artisan::call('migrate', ['--force' => true]);

            (new PlatformSeeder)->run();

            $admin = User::where('email', $data['admin_email'])->first() ?? new User;
            $admin->forceFill([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'staff_role' => 'super_admin',
                'is_hirer' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
            ])->save();

            if (! empty($data['demo'])) {
                (new DemoSeeder)->run();
            }

            $mail = filled($data['mail_host']);
            app(Settings::class)->set(array_filter([
                'site_name' => $data['site_name'],
                'contact_email' => $data['contact_email'],
                'ai_api_key' => $data['ai_api_key'] ?? null,
                'ai_enabled' => filled($data['ai_api_key'] ?? null),
                'require_email_verification' => $mail,
            ], fn ($v) => $v !== null));

            $env = ['APP_URL' => rtrim($data['app_url'], '/'), 'APP_NAME' => $data['site_name'], 'APP_ENV' => 'production', 'APP_DEBUG' => 'false'];
            if ($mail) {
                $env += [
                    'MAIL_MAILER' => 'smtp',
                    'MAIL_HOST' => $data['mail_host'],
                    'MAIL_PORT' => $data['mail_port'] ?: 587,
                    'MAIL_USERNAME' => $data['mail_username'] ?? '',
                    'MAIL_PASSWORD' => $data['mail_password'] ?? '',
                    'MAIL_FROM_ADDRESS' => $data['mail_from'] ?: $data['contact_email'],
                ];
            }
            EnvWriter::set($env);

            file_put_contents(storage_path('app/installed.lock'), 'Installed '.now()->toIso8601String());
            try {
                Artisan::call('optimize:clear');
            } catch (Throwable) {
                // non-fatal
            }
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['install' => 'Installation failed: '.$e->getMessage()]);
        }

        session()->forget('install.db');
        auth()->login($admin);

        return redirect()->route('admin.dashboard')->with('success', 'You’re live 🎉 Welcome to your Devhail command centre.');
    }

    protected function applyDatabase(array $data): void
    {
        if ($data['driver'] === 'sqlite') {
            $file = database_path('database.sqlite');
            if (! file_exists($file)) {
                touch($file);
            }
            Config::set('database.default', 'sqlite');
            Config::set('database.connections.sqlite.database', $file);
        } else {
            Config::set('database.default', 'mysql');
            Config::set('database.connections.mysql', array_merge(config('database.connections.mysql'), [
                'host' => $data['host'],
                'port' => $data['port'],
                'database' => $data['database'],
                'username' => $data['username'],
                'password' => $data['password'] ?? '',
            ]));
        }
        DB::purge();
        DB::reconnect();
    }
}
