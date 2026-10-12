<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Milestone;
use App\Models\PaymentTransaction;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function checkout(Request $request, PaymentTransaction $transaction)
    {
        $this->owns($request, $transaction);
        if ($transaction->status !== 'pending') {
            return redirect($this->returnUrl($transaction))->with('info', 'This payment has already been processed.');
        }

        try {
            return redirect()->away($this->payments->gateway($transaction->provider)->checkoutUrl($transaction));
        } catch (Throwable $e) {
            report($e);
            $this->releaseMilestoneHold($transaction);

            return redirect($this->returnUrl($transaction))->with('error', 'We couldn’t start the payment: '.$e->getMessage());
        }
    }

    /** The payer lands here after checkout. We still verify server-to-server before trusting anything. */
    public function callback(Request $request, PaymentTransaction $transaction)
    {
        $this->owns($request, $transaction);
        $transaction = $this->payments->confirm($transaction);

        return redirect($this->returnUrl($transaction))->with(...match ($transaction->status) {
            'succeeded' => ['success', 'Payment confirmed by the provider ✅'],
            'failed' => ['error', 'The payment didn’t go through. No money moved — try again whenever you’re ready.'],
            default => ['info', 'Payment is still processing. We’ll update this page as soon as the provider confirms it.'],
        });
    }

    public function sandbox(Request $request, PaymentTransaction $transaction)
    {
        $this->owns($request, $transaction);
        abort_unless($transaction->provider === 'sandbox' && $transaction->status === 'pending', 404);

        return view('payments.sandbox', ['tx' => $transaction]);
    }

    public function sandboxComplete(Request $request, PaymentTransaction $transaction)
    {
        $this->owns($request, $transaction);
        abort_unless($transaction->provider === 'sandbox' && $transaction->status === 'pending', 404);
        $outcome = $request->validate(['outcome' => 'required|in:success,fail'])['outcome'];
        $transaction->update(['metadata' => array_merge($transaction->metadata ?? [], ['sandbox_outcome' => $outcome])]);

        return redirect()->route('payments.callback', $transaction);
    }

    public function paystackWebhook(Request $request)
    {
        $gateway = $this->payments->paystack();
        if (! $gateway->validWebhook($request->getContent(), $request->header('x-paystack-signature'))) {
            AuditLog::record('webhook.invalid_signature', null, ['ip' => $request->ip()]);
            abort(401);
        }

        $reference = $request->input('data.reference');
        if ($reference && ($tx = PaymentTransaction::where('reference', $reference)->where('provider', 'paystack')->first())) {
            // Never trust the webhook body for the outcome: re-verify with the API.
            $this->payments->confirm($tx);
        }

        return response()->json(['ok' => true]);
    }

    protected function owns(Request $request, PaymentTransaction $tx): void
    {
        abort_unless($tx->user_id === $request->user()->id || $request->user()->hasStaffRole('finance', 'admin'), 403);
    }

    protected function releaseMilestoneHold(PaymentTransaction $tx): void
    {
        if ($tx->payable instanceof Milestone && $tx->payable->status === 'funding_pending') {
            $tx->payable->update(['status' => 'pending']);
            $tx->update(['status' => 'failed']);
        }
    }

    protected function returnUrl(PaymentTransaction $tx): string
    {
        return match ($tx->purpose) {
            'milestone_funding' => route('app.contracts.show', $tx->payable->contract),
            default => route('app.billing'),
        };
    }
}
