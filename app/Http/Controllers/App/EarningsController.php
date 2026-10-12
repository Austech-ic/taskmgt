<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\Ledger;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function index(Request $request, Ledger $ledger)
    {
        $user = $request->user();
        $account = $ledger->payableAccount($user);
        $pending = (int) $user->payoutRequests()->whereIn('status', ['requested', 'approved'])->sum('amount_minor');

        return view('app.talent.earnings', [
            'balance' => $account->balance(),
            'pending' => $pending,
            'available' => max(0, $account->balance() - $pending),
            'entries' => LedgerEntry::where('ledger_account_id', $account->id)->latest('id')->paginate(15),
            'payouts' => $user->payoutRequests()->latest()->take(10)->get(),
        ]);
    }

    public function requestPayout(Request $request, Ledger $ledger)
    {
        $user = $request->user();
        $data = $request->validate([
            'amount' => 'required|string|max:20',
            'bank_name' => 'required|string|max:80',
            'account_number' => 'required|digits_between:10,12',
            'account_name' => 'required|string|max:120',
        ]);
        $amount = to_minor($data['amount']);
        $pending = (int) $user->payoutRequests()->whereIn('status', ['requested', 'approved'])->sum('amount_minor');
        $available = $ledger->balanceOf($user) - $pending;

        if (! $amount || $amount < 100000) {
            return back()->with('error', 'Minimum payout is ₦1,000.');
        }
        if ($amount > $available) {
            return back()->with('error', 'That’s more than your available balance ('.money($available).').');
        }

        $payout = PayoutRequest::create(['user_id' => $user->id, 'amount_minor' => $amount, 'currency' => 'NGN'] + collect($data)->except('amount')->all());
        AuditLog::record('payout.requested', $payout, ['amount' => $amount]);
        User::whereIn('staff_role', ['finance', 'super_admin'])->get()->each->notify(new PlatformNotification('Payout requested', $user->name.' requested '.money($amount).'.', route('admin.finance.payouts'), 'banknote'));

        return back()->with('success', 'Payout requested 💸 Our finance team processes these within 1–2 working days.');
    }
}
