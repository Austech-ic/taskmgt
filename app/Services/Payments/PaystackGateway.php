<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway implements Gateway
{
    protected const BASE = 'https://api.paystack.co';

    public function __construct(protected string $secretKey) {}

    public function checkoutUrl(PaymentTransaction $tx): string
    {
        $response = Http::withToken($this->secretKey)->acceptJson()->timeout(20)
            ->post(self::BASE.'/transaction/initialize', [
                'email' => $tx->user->email,
                'amount' => $tx->amount_minor,
                'currency' => $tx->currency,
                'reference' => $tx->reference,
                'callback_url' => route('payments.callback', $tx),
                'metadata' => ['purpose' => $tx->purpose, 'devhail_tx' => $tx->id],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Paystack could not start this payment: '.($response->json('message') ?? 'unknown error'));
        }

        return $response->json('data.authorization_url');
    }

    public function verify(PaymentTransaction $tx): array
    {
        $response = Http::withToken($this->secretKey)->acceptJson()->timeout(20)
            ->get(self::BASE.'/transaction/verify/'.rawurlencode($tx->reference));

        $data = (array) $response->json('data', []);
        $status = match ($data['status'] ?? null) {
            'success' => 'succeeded',
            'failed', 'abandoned', 'reversed' => 'failed',
            default => 'pending',
        };

        return [
            'status' => $response->successful() ? $status : 'pending',
            'amount_minor' => isset($data['amount']) ? (int) $data['amount'] : null,
            'currency' => $data['currency'] ?? null,
            'provider_reference' => isset($data['id']) ? (string) $data['id'] : null,
            'payload' => $data,
        ];
    }

    public function validWebhook(string $payload, ?string $signature): bool
    {
        return $signature !== null && hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature);
    }
}
