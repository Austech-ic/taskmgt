<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;

/**
 * Test-mode gateway for demos and staging. It never moves real money:
 * the payer confirms on a Devhail-hosted test page, and the outcome is recorded
 * in the transaction's metadata, which verify() then reads back.
 */
class SandboxGateway implements Gateway
{
    public function checkoutUrl(PaymentTransaction $tx): string
    {
        return route('payments.sandbox', $tx);
    }

    public function verify(PaymentTransaction $tx): array
    {
        $outcome = $tx->metadata['sandbox_outcome'] ?? null;

        return [
            'status' => match ($outcome) {
                'success' => 'succeeded',
                'fail' => 'failed',
                default => 'pending',
            },
            'amount_minor' => $outcome ? $tx->amount_minor : null,
            'currency' => $tx->currency,
            'provider_reference' => $outcome ? 'SANDBOX-'.$tx->id : null,
            'payload' => ['sandbox' => true, 'outcome' => $outcome],
        ];
    }
}
