<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public const CREDIT_BUNDLES = [1, 5, 10];

    public function index(Request $request)
    {
        $org = $request->user()->organization();

        return view('app.hirer.billing', [
            'org' => $org,
            'subscription' => $org->activeSubscription()?->load('plan'),
            'plans' => Plan::where('is_active', true)->where('is_custom', false)->where('price_minor', '>', 0)->orderBy('position')->get(),
            'bundles' => self::CREDIT_BUNDLES,
            'unitPrice' => (int) setting('unlock_price_minor'),
            'transactions' => PaymentTransaction::where('organization_id', $org->id)->latest()->take(15)->get(),
        ]);
    }

    public function subscribe(Request $request, Plan $plan, PaymentService $payments)
    {
        abort_unless($plan->is_active && ! $plan->is_custom && $plan->price_minor > 0, 404);
        $org = $request->user()->organization();
        $tx = $payments->create($request->user(), 'subscription', $plan->price_minor, $plan->currency, $plan, $org, ['plan_id' => $plan->id]);

        return redirect()->route('payments.checkout', $tx);
    }

    public function credits(Request $request, PaymentService $payments)
    {
        $qty = (int) $request->validate(['quantity' => 'required|in:'.implode(',', self::CREDIT_BUNDLES)])['quantity'];
        $org = $request->user()->organization();
        $tx = $payments->create($request->user(), 'contact_credits', $qty * (int) setting('unlock_price_minor'), setting('currency', 'NGN'), null, $org, ['credits' => $qty]);

        return redirect()->route('payments.checkout', $tx);
    }
}
