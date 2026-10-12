<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;

interface Gateway
{
    /** Return the URL the payer should be redirected to. */
    public function checkoutUrl(PaymentTransaction $tx): string;

    /**
     * Ask the provider (server-to-server) whether this transaction really succeeded.
     *
     * @return array{status: 'succeeded'|'failed'|'pending', amount_minor: ?int, currency: ?string, provider_reference: ?string, payload: array}
     */
    public function verify(PaymentTransaction $tx): array;
}
