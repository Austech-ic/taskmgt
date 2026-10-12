# Devhail ⚡

**Proven skills. Trusted talent. Delivered projects.**

Devhail is a verified technology-talent and delivery platform built with Laravel 12. Professionals prove their skills in assessments before they get verified badges; employers search and recruit them; clients hire them for projects paid by milestone. It ships as a complete, self-installable content-managed site that runs on ordinary **shared hosting** (cPanel, no SSH, no Node build, no Redis or queue workers).

---

## What's inside

| Area | Highlights |
|---|---|
| **Talent** | Onboarding checklist, profile + portfolio + experience + certifications, controlled skill taxonomy (46 skills / 11 categories), public shareable profile with privacy controls, CV upload (private storage) |
| **Assessments** | 8 published assessments with 80 original seed questions, randomised selection with exposure tracking, server-side scoring (answer keys never reach the browser), timed exam UI with autosave, retake cooldowns, badge expiry, practical briefs with human review, integrity signals that route to a reviewer instead of auto-failing anyone, 5 proficiency levels |
| **Discovery** | Search by skill, verified level, location, availability, arrangement and experience. Ranking is evidence-based and never paid. Saved searches, shortlists with notes/ratings, side-by-side compare, CSV export |
| **Recruitment** | Company profiles + team seats, contact unlocks (subscription allowance or pay-per-contact credits), contact requests for people who opt out of direct contact, job posts, application pipeline (kanban) with audit trail |
| **Marketplace** | Projects → proposals with milestones → contracts → funding → deliverables (versioned, private files) → approve / revision / dispute → release → multi-dimension reviews |
| **Money** | Paystack integration (initialize, verify, signed webhooks) plus a sandbox test gateway. Payments only count as funded after server-side verification. Append-only **double-entry ledger** in integer kobo, configurable commission (default 15%, payer: professional, client or split), dispute settlement (release / refund / split), payout requests with **dual control** |
| **AI** (Anthropic Claude) | Profile "glow-up", plain-English talent search → filters, job post writer, project scoper with milestone suggestions, proposal drafts, blog drafts, assessment question drafts (saved inactive for human review), and **Hail**, a help assistant grounded in your FAQs. All optional; everything degrades gracefully when AI is off |
| **CMS & admin** | Pages, blog, FAQs, testimonials, skill taxonomy, plans, brand colours, homepage copy, announcement bar, analytics snippet. Role-based admin (reviewer, finance, admin, super admin) for approvals, assessment authoring, review queue, users, companies, listing and review moderation, disputes, transactions, ledger, payouts and the audit log |
| **Design** | Custom design system (warm paper and ink, electric violet and lime, Bricolage Grotesque type, sticker badges), light and dark mode, fully responsive, accessible focus states and keyboard-operable controls |

## Install on shared hosting (no terminal needed)

1. **Get a release zip** with `vendor/` included. Run `scripts/build-release.sh` on any machine that has PHP and Composer; it writes `dist/devhail-<version>.zip`.
2. **Upload and extract** it in your hosting file manager.
   - **Best:** point your domain's document root at the `public/` folder.
   - **If you can't change the document root:** extract into `public_html/`. The root `.htaccess` routes everything into `public/` and blocks access to `.env`, `vendor/`, `storage/` and the other sensitive folders.
3. **Create a MySQL database and user** (cPanel → MySQL Databases).
4. **Visit your domain.** The web installer opens and walks you through:
   - a server check (PHP 8.2+, required extensions, writable folders);
   - your database details, which it tests before saving;
   - the site name, URL and admin account, plus optional SMTP, an Anthropic API key and demo content.
5. Done. The installer creates `.env` with a fresh app key, runs migrations, seeds the platform content and then locks itself (`storage/app/installed.lock`).

**Permissions:** `storage/` and `bootstrap/cache/` must be writable (usually 775).
**Uploads:** files are served through `/media/…`, so no `storage:link` symlink is needed.
**Cron (optional):** `* * * * * php /path/to/artisan schedule:run`. Nothing currently requires it.

## Going live checklist

- [ ] **Payments:** switch from *Sandbox* to *Paystack* in Admin → Settings → Payments, and add the webhook URL shown there to your Paystack dashboard.
- [ ] **Legal:** before taking real milestone payments, confirm with your payment provider and a lawyer how client funds are held (escrow or collection) and whether your model needs licensing. Devhail only marks money as funded after the provider confirms it.
- [ ] **Policies:** review the starter Terms and Privacy pages (written as templates, see NDPA 2023 / NDPC).
- [ ] **Testimonials:** replace the clearly labelled sample testimonials with real ones.
- [ ] **Email:** add SMTP to `.env` (or set it in the installer), then enable email verification in Admin → Settings → Access.
- [ ] **AI (optional):** add your Anthropic API key and use "Test AI connection".
- [ ] **Demo data:** delete the demo accounts (`*@demo.devhail.test`) if you seeded them.
- [ ] **Assessments:** have subject-matter experts review, extend and rotate the question banks.

## AI configuration

AI uses the official [Anthropic PHP SDK](https://github.com/anthropics/anthropic-sdk-php) and is configured in **Admin → Settings → AI**:

- **Model:** `claude-opus-5-5` (default, best quality), `claude-sonnet-5-5` or `claude-haiku-5-5` (fastest and cheapest).
- **Effort:** defaults to `low`, which keeps responses fast within shared-hosting PHP time limits.
- **Daily limit:** caps AI requests per user per day.
- **Safety fallbacks:** server-side refusal fallbacks are enabled, so a request declined by safety classifiers is retried on a fallback model automatically.
- **Storage:** the API key is stored encrypted with your app key.

AI never scores assessments, approves profiles or moves money. Every AI call is logged (feature, model, token counts) without the prompt content.

## Local development

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed          # platform content + demo data + admin@devhail.test / password
touch storage/app/installed.lock    # skip the web installer locally
php artisan serve
```

Demo logins (password `password`): `adaeze.okafor@demo.devhail.test` (talent), `hiring@demo.devhail.test` (employer), `client@demo.devhail.test` (client).

### Tests

```bash
php artisan test
```

The suite covers assessment integrity (answer keys never sent to the browser, a failed attempt never shows as verified, practical work goes to human review), the full milestone lifecycle with commission and ledger balance, failed payments, split dispute settlement, dual-control payouts, contact privacy and unlocks, search visibility, the jobs pipeline, role enforcement, graceful AI degradation, and a smoke test that renders every page for each audience.

## Architecture notes

- A **modular monolith**: `app/Services` holds the high-risk logic (`AssessmentService`, `ContractService`, `Ledger`, `Payments/*`, `Ai/*`) behind small interfaces, and controllers stay thin.
- **Money** is stored as integer minor units and never as floats. Ledger entries are immutable at the model level, and corrections are made with reversing journals.
- **Settings** live in the database and are cached. Secrets are encrypted.
- **No build step:** the CSS (custom properties), Alpine.js and Lucide icons are vendored in `public/`.
- **Shared-hosting friendly:** file sessions and cache, sync queue, a pre-install `.env` bootstrap and the `/media` file route.

## Roadmap (next phases)

Supervised or live assessments, coding sandboxes for hands-on tests, multi-currency (USD) payouts, Paystack transfer automation, recruiter analytics, enterprise private talent pools, and API access.
