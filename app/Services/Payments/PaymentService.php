<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\Ledger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(protected Ledger $ledger) {}

    public function gateway(?string $provider = null): Gateway
    {
        $provider ??= setting('payment_provider');

        return match ($provider) {
            'paystack' => new PaystackGateway((string) setting('paystack_secret_key')),
            'sandbox' => new SandboxGateway,
            default => throw new RuntimeException("Unknown payment provider [$provider]."),
        };
    }

    public function paystack(): PaystackGateway
    {
        return new PaystackGateway((string) setting('paystack_secret_key'));
    }

    public function create(User $user, string $purpose, int $amountMinor, string $currency, ?Model $payable = null, ?Organization $org = null, array $metadata = []): PaymentTransaction
    {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        return PaymentTransaction::create([
            'reference' => 'DH-'.strtoupper(Str::random(4)).'-'.now()->format('ymdHis').'-'.random_int(100, 999),
            'user_id' => $user->id,
            'organization_id' => $org?->id,
            'purpose' => $purpose,
            'payable_type' => $payable?->getMorphClass(),
            'payable_id' => $payable?->getKey(),
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'provider' => setting('payment_provider'),
            'status' => 'pending',
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * Server-side confirmation with the provider. This — never a client-side
     * redirect or button — is what marks a transaction (and milestone) as paid.
     */
    public function confirm(PaymentTransaction $tx): PaymentTransaction
    {
        if ($tx->status !== 'pending') {
            return $tx;
        }

        $result = $this->gateway($tx->provider)->verify($tx);

        if ($result['status'] === 'pending') {
            return $tx;
        }

        if ($result['status'] === 'succeeded'
            && ($result['amount_minor'] !== $tx->amount_minor || strtoupper((string) $result['currency']) !== $tx->currency)) {
            $tx->update(['status' => 'failed', 'provider_payload' => $result['payload'] + ['devhail_error' => 'amount_or_currency_mismatch']]);
            AuditLog::record('payment.mismatch', $tx, ['expected' => $tx->amount_minor, 'got' => $result['amount_minor']]);

            return $tx;
        }

        DB::transaction(function () use ($tx, $result) {
            $locked = PaymentTransaction::whereKey($tx->id)->lockForUpdate()->first();
            if ($locked->status !== 'pending') {
                return;
            }

            $locked->update([
                'status' => $result['status'],
                'provider_reference' => $result['provider_reference'],
                'provider_payload' => $result['payload'],
                'confirmed_at' => $result['status'] === 'succeeded' ? now() : null,
            ]);

            if ($result['status'] === 'succeeded') {
                $this->fulfil($locked);
            } elseif ($locked->purpose === 'milestone_funding' && $locked->payable instanceof Milestone && $locked->payable->status === 'funding_pending') {
                $locked->payable->update(['status' => 'pending']);
            }
        });

        return $tx->refresh();
    }

    protected function fulfil(PaymentTransaction $tx): void
    {
        $cash = $this->ledger->account('platform:cash', $tx->currency);

        switch ($tx->purpose) {
            case 'milestone_funding':
                /** @var Milestone $milestone */
                $milestone = $tx->payable;
                $this->ledger->post("Milestone funded · {$milestone->contract->reference} #{$milestone->position}", [
                    [$cash, $tx->amount_minor, 0],
                    [$this->ledger->account('escrow:held', $tx->currency), 0, $tx->amount_minor],
                ], $tx, $tx->currency);
                $milestone->update(['status' => 'funded', 'funded_at' => now()]);
                if ($milestone->contract->status === 'pending_acceptance' && $milestone->contract->professional_accepted_at) {
                    $milestone->contract->update(['status' => 'active']);
                }
                $milestone->contract->professional->notify(new PlatformNotification(
                    'Milestone funded 💸',
                    "“{$milestone->title}” is funded. You're clear to start shipping.",
                    route('app.contracts.show', $milestone->contract),
                    'wallet'
                ));
                break;

            case 'contact_credits':
                $credits = (int) ($tx->metadata['credits'] ?? 0);
                $org = $tx->organization;
                $org->forceFill(['unlock_credits' => $org->unlock_credits + $credits])->save();
                $this->ledger->post("Contact unlock credits ×{$credits} · {$org->name}", [
                    [$cash, $tx->amount_minor, 0],
                    [$this->ledger->account('revenue:unlocks', $tx->currency), 0, $tx->amount_minor],
                ], $tx, $tx->currency);
                break;

            case 'subscription':
                $plan = Plan::findOrFail($tx->metadata['plan_id']);
                $org = $tx->organization;
                $org->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);
                Subscription::create([
                    'organization_id' => $org->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'unlocks_remaining' => $plan->contact_unlocks,
                    'current_period_start' => now(),
                    'current_period_end' => $plan->interval === 'year' ? now()->addYear() : now()->addMonth(),
                ]);
                $this->ledger->post("Subscription · {$plan->name} · {$org->name}", [
                    [$cash, $tx->amount_minor, 0],
                    [$this->ledger->account('revenue:subscriptions', $tx->currency), 0, $tx->amount_minor],
                ], $tx, $tx->currency);
                break;
        }

        AuditLog::record('payment.confirmed', $tx, ['purpose' => $tx->purpose, 'amount' => $tx->amount_minor]);
    }
}
