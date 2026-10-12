<?php

use App\Support\Labels;
use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('money')) {
    /** Format integer minor units (kobo/cents) as a display string. */
    function money(int|float|null $minor, string $currency = 'NGN', bool $decimals = true): string
    {
        $symbols = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', 'KES' => 'KSh ', 'GHS' => 'GH₵', 'ZAR' => 'R'];
        $symbol = $symbols[$currency] ?? $currency.' ';
        $major = ((int) $minor) / 100;

        if (! $decimals && $major >= 1000000) {
            return $symbol.rtrim(rtrim(number_format($major / 1000000, 1), '0'), '.').'M';
        }
        if (! $decimals && $major >= 10000) {
            return $symbol.rtrim(rtrim(number_format($major / 1000, 1), '0'), '.').'k';
        }

        return $symbol.number_format($major, $decimals && fmod($major, 1) !== 0.0 ? 2 : 0);
    }
}

if (! function_exists('to_minor')) {
    /** Convert a user-entered major amount ("150,000.50") to integer minor units without floats. */
    function to_minor(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = preg_replace('/[^0-9.]/', '', (string) $value);
        if ($clean === '' || substr_count($clean, '.') > 1) {
            return null;
        }
        [$whole, $fraction] = array_pad(explode('.', $clean, 2), 2, '0');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return ((int) $whole) * 100 + (int) $fraction;
    }
}

if (! function_exists('to_major')) {
    function to_major(?int $minor): ?string
    {
        if ($minor === null) {
            return null;
        }

        return $minor % 100 === 0 ? (string) intdiv($minor, 100) : number_format($minor / 100, 2, '.', '');
    }
}

if (! function_exists('level_name')) {
    function level_name(?int $level): string
    {
        return Labels::LEVELS[$level]['name'] ?? 'Unverified';
    }
}

if (! function_exists('workspace')) {
    /** Active workspace for the signed-in user: "talent" or "hirer". */
    function workspace(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        $ws = session('workspace');
        if ($ws === 'talent' && $user->is_talent) {
            return 'talent';
        }
        if ($ws === 'hirer' && $user->is_hirer) {
            return 'hirer';
        }

        return $user->is_talent ? 'talent' : ($user->is_hirer ? 'hirer' : null);
    }
}

if (! function_exists('is_installed')) {
    function is_installed(): bool
    {
        if (app()->runningUnitTests() && ! ($GLOBALS['devhail_test_installer'] ?? false)) {
            return true;
        }

        return file_exists(storage_path('app/installed.lock'));
    }
}
