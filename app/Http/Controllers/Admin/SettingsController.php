<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Ai\AiService;
use App\Services\Ai\AiUnavailable;
use App\Support\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /** Group => [field => [label, type, rules, hint?]] */
    public const GROUPS = [
        'general' => [
            'title' => 'Brand & site',
            'icon' => 'palette',
            'fields' => [
                'site_name' => ['Site name', 'text', 'required|string|max:60'],
                'site_tagline' => ['Tagline', 'text', 'required|string|max:120'],
                'site_description' => ['SEO description', 'textarea', 'required|string|max:300'],
                'contact_email' => ['Public contact email', 'email', 'required|email'],
                'support_phone' => ['Support phone', 'text', 'nullable|string|max:40'],
                'brand_primary' => ['Primary colour', 'color', 'required|regex:/^#[0-9a-fA-F]{6}$/', 'Buttons, links, highlights'],
                'brand_accent' => ['Accent colour', 'color', 'required|regex:/^#[0-9a-fA-F]{6}$/', 'Stickers, CTAs, marquee'],
                'social_x' => ['X / Twitter URL', 'url', 'nullable|url'],
                'social_instagram' => ['Instagram URL', 'url', 'nullable|url'],
                'social_linkedin' => ['LinkedIn URL', 'url', 'nullable|url'],
                'social_tiktok' => ['TikTok URL', 'url', 'nullable|url'],
                'analytics_snippet' => ['Analytics snippet', 'textarea', 'nullable|string|max:3000', 'Pasted into <head> on public pages (e.g. Google Analytics, Plausible).'],
            ],
        ],
        'homepage' => [
            'title' => 'Homepage copy',
            'icon' => 'layout-template',
            'fields' => [
                'announcement' => ['Announcement bar', 'text', 'nullable|string|max:160', 'Leave empty to hide.'],
                'hero_eyebrow' => ['Hero sticker', 'text', 'required|string|max:60'],
                'hero_title' => ['Hero headline', 'text', 'required|string|max:90', 'The words “ship” or “actually” get the highlighter effect.'],
                'hero_subtitle' => ['Hero subtitle', 'textarea', 'required|string|max:300'],
            ],
        ],
        'marketplace' => [
            'title' => 'Marketplace & pricing',
            'icon' => 'percent',
            'fields' => [
                'currency' => ['Default currency', 'select:NGN,USD', 'required|in:NGN,USD'],
                'commission_bps' => ['Commission (basis points)', 'number', 'required|integer|between:0,5000', '1500 = 15%. Applies to new contracts only.'],
                'commission_payer' => ['Who pays commission', 'select:professional,client,split', 'required|in:professional,client,split'],
                'unlock_price_minor' => ['Contact unlock price (kobo)', 'number', 'required|integer|min:0', '300000 = ₦3,000'],
                'max_revisions' => ['Revision requests per milestone', 'number', 'required|integer|between:0,10'],
                'free_assessments' => ['Free assessment attempts per user', 'number', 'required|integer|between:0,100'],
            ],
        ],
        'access' => [
            'title' => 'Access & verification',
            'icon' => 'shield-check',
            'fields' => [
                'allow_registration' => ['Allow new sign-ups', 'bool', 'nullable|boolean'],
                'require_email_verification' => ['Require email verification', 'bool', 'nullable|boolean', 'Only enable once SMTP is configured in .env.'],
                'auto_conditional_approval' => ['Auto conditional approval', 'bool', 'nullable|boolean', 'Profiles that pass their primary-skill assessment go live conditionally while admins complete the review.'],
            ],
        ],
        'payments' => [
            'title' => 'Payments',
            'icon' => 'credit-card',
            'fields' => [
                'payment_provider' => ['Payment provider', 'select:sandbox,paystack', 'required|in:sandbox,paystack', 'Sandbox = test mode, no real money.'],
                'paystack_public_key' => ['Paystack public key', 'text', 'nullable|string|max:200'],
                'paystack_secret_key' => ['Paystack secret key', 'secret', 'nullable|string|max:200', 'Stored encrypted. Leave blank to keep the current key. Webhook URL: {webhook}'],
            ],
        ],
        'ai' => [
            'title' => 'AI',
            'icon' => 'sparkles',
            'fields' => [
                'ai_enabled' => ['Enable AI features', 'bool', 'nullable|boolean'],
                'ai_api_key' => ['Anthropic API key', 'secret', 'nullable|string|max:300', 'Stored encrypted. Leave blank to keep the current key.'],
                'ai_model' => ['Model', 'select:claude-opus-5-5,claude-sonnet-5-5,claude-haiku-5-5', 'required|string|max:60', 'Opus = best quality, Sonnet = balanced, Haiku = fastest & cheapest.'],
                'ai_effort' => ['Effort', 'select:low,medium,high', 'required|in:low,medium,high', 'Low keeps responses fast on shared hosting.'],
                'ai_daily_limit' => ['AI requests per user per day', 'number', 'required|integer|between:1,1000'],
            ],
        ],
    ];

    public function edit(?string $group = 'general')
    {
        $group ??= 'general';
        abort_unless(isset(self::GROUPS[$group]), 404);
        if ($group === 'payments' && ! request()->user()->hasStaffRole('admin')) {
            abort(403);
        }

        return view('admin.settings', ['groups' => self::GROUPS, 'group' => $group, 'values' => app(Settings::class)->all()]);
    }

    public function update(Request $request, string $group, Settings $settings)
    {
        abort_unless(isset(self::GROUPS[$group]), 404);
        $fields = self::GROUPS[$group]['fields'];
        $data = $request->validate(collect($fields)->map(fn ($f) => $f[2])->all());

        foreach ($fields as $key => $f) {
            if ($f[1] === 'bool') {
                $data[$key] = $request->boolean($key);
            }
            if ($f[1] === 'secret' && blank($data[$key] ?? null)) {
                unset($data[$key]); // keep existing secret
            }
        }
        if (in_array($group, ['payments', 'marketplace'], true)) {
            abort_unless($request->user()->staff_role === 'super_admin' || $request->user()->staff_role === 'admin', 403);
        }

        $settings->set($data);
        AuditLog::record('settings.updated', null, ['group' => $group, 'keys' => array_keys($data)]);

        return back()->with('success', self::GROUPS[$group]['title'].' saved.');
    }

    public function testAi(AiService $ai)
    {
        try {
            $reply = $ai->ask('settings_test', 'Reply with one short, upbeat sentence confirming you are connected. No more than 15 words.', 'Connection test from the admin console.', 100);
        } catch (AiUnavailable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'AI connected ✅ — “'.$reply.'”');
    }
}
