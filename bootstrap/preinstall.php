<?php

/*
|--------------------------------------------------------------------------
| First-run bootstrap for shared hosting
|--------------------------------------------------------------------------
| Shared hosts rarely give you SSH, so `cp .env.example .env && php artisan
| key:generate` can't be run by hand. On the very first request we create
| the .env file with a fresh APP_KEY so the web installer can take over.
*/

$base = dirname(__DIR__);
$env = $base.'/.env';

if (! file_exists($env) && file_exists($base.'/.env.example') && is_writable($base)) {
    $contents = file_get_contents($base.'/.env.example');
    $key = 'base64:'.base64_encode(random_bytes(32));
    $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents);
    $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    if (! empty($_SERVER['HTTP_HOST'])) {
        $path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $path = preg_replace('#/public$#', '', $path);
        $contents = preg_replace('/^APP_URL=.*$/m', 'APP_URL='.$scheme.'://'.$_SERVER['HTTP_HOST'].$path, $contents);
    }
    file_put_contents($env, $contents);
}

foreach (['storage/app/public', 'storage/app/private', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
    if (! is_dir($base.'/'.$dir)) {
        @mkdir($base.'/'.$dir, 0775, true);
    }
}
