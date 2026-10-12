<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Database-backed site settings managed from the admin console.
 * Secret values (API keys) are encrypted at rest with the app key.
 */
class Settings
{
    public const DEFAULTS = [
        'site_name' => 'Devhail',
        'site_tagline' => 'Proven skills. Trusted talent. Delivered projects.',
        'site_description' => 'Devhail is where verified tech talent gets discovered, hired and paid. Skills are tested, not claimed.',
        'contact_email' => 'hello@devhail.com',
        'support_phone' => '',
        'hero_eyebrow' => 'Skills > vibes. Receipts > résumés.',
        'hero_title' => 'Hire tech talent that can actually ship.',
        'hero_subtitle' => 'Every Devhail pro proves their skills in hands-on assessments before they get a verified badge. Find them, hire them, pay them by milestone — all in one place.',
        'announcement' => '🚀 Early access is live in Nigeria — first 3 skill assessments are on us.',
        'social_x' => '',
        'social_instagram' => '',
        'social_linkedin' => '',
        'social_tiktok' => '',
        'currency' => 'NGN',
        'commission_bps' => 1500,
        'commission_payer' => 'professional',
        'unlock_price_minor' => 300000,
        'free_assessments' => 3,
        'max_revisions' => 2,
        'allow_registration' => true,
        'require_email_verification' => false,
        'auto_conditional_approval' => true,
        'payment_provider' => 'sandbox',
        'paystack_public_key' => '',
        'paystack_secret_key' => '',
        'ai_enabled' => false,
        'ai_api_key' => '',
        'ai_model' => 'claude-opus-5-5',
        'ai_effort' => 'low',
        'ai_daily_limit' => 30,
        'brand_accent' => '#c6ff3d',
        'brand_primary' => '#6d4aff',
        'analytics_snippet' => '',
    ];

    public const SECRETS = ['paystack_secret_key', 'ai_api_key'];

    protected ?array $values = null;

    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $stored = [];
        try {
            if (is_installed() && Schema::hasTable('settings')) {
                $stored = Cache::rememberForever('devhail.settings', fn () => Setting::pluck('value', 'key')->all());
            }
        } catch (Throwable) {
            $stored = [];
        }

        $values = self::DEFAULTS;
        foreach ($stored as $key => $value) {
            if (in_array($key, self::SECRETS, true) && $value !== null && $value !== '') {
                try {
                    $value = Crypt::decryptString($value);
                } catch (Throwable) {
                    $value = '';
                }
            }
            $values[$key] = $this->cast($key, $value);
        }

        return $this->values = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $secret = in_array($key, self::SECRETS, true);
            Setting::updateOrCreate(['key' => $key], [
                'value' => $secret && $value !== null && $value !== '' ? Crypt::encryptString((string) $value) : $value,
                'is_secret' => $secret,
            ]);
        }
        Cache::forget('devhail.settings');
        $this->values = null;
    }

    protected function cast(string $key, mixed $value): mixed
    {
        $default = self::DEFAULTS[$key] ?? null;

        return match (true) {
            is_bool($default) => in_array($value, [true, 1, '1', 'true', 'on'], true),
            is_int($default) => (int) $value,
            default => $value ?? '',
        };
    }
}
