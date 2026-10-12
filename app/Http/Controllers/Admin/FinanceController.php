<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\PaymentTransaction;
use App\Models\PayoutRequest;
use App\Notifications\PlatformNotification;
use App\Services\Ledger;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Ledger $ledger)
    {
        $accounts = LedgerAccount::whereNull('user_id')->get()->map(fn ($a) => ['account' => $a, 'balance' => $a->balance()]);
        $payables = (int) LedgerAccount::whereNotNull('user_id')->get()->sum(fn ($a) => $a->balance());

        return view('admin.finance.index', [
            'accounts' => $accounts,
            'payables' => $payables,
            'balanced' => $ledger->isBalanced(),
            'volume' => PaymentTransaction::where('status', 'succeeded')->selectRaw('purpose, count(*) as n, sum(amount_minor) as total')->groupBy('purpose')->get(),
            'pendingTx' => PaymentTransaction::where('status', 'pending')->where('created_at', '<', now()->subMinutes(30))->count(),
        ]);
    }

    public function transactions(Request $request)
    {
        return view('admin.finance.transactions', [
            'transactions' => PaymentTransaction::with(['user', 'organization'])
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->q, fn ($q, $t) => $q->where('reference', 'like', "%$t%"))
                ->latest()->paginate(30)->withQueryString(),
        ]);
    }

    /** Re-verify a pending transaction with the provider (e.g. if a webhook was missed). */
    public function recheck(PaymentTransaction $transaction, PaymentService $payments)
    {
        $before = $transaction->status;
        $after = $payments->confirm($transaction)->status;
        AuditLog::record('payment.rechecked', $transaction, compact('before', 'after'));

        return back()->with('success', "Re-checked with provider: {$before} → {$after}.");
    }

    public function ledger(Request $request)
    {
        return view('admin.finance.ledger', [
            'entries' => LedgerEntry::with('account')->when($request->account, fn ($q, $a) => $q->where('ledger_account_id', $a))->latest('id')->paginate(50)->withQueryString(),
            'accounts' => LedgerAccount::orderBy('code')->get(),
        ]);
    }

    public function payouts(Request $request)
    {
        return view('admin.finance.payouts', [
            'payouts' => PayoutRequest::with(['user', 'approver'])->when($request->status, fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->whereIn('status', ['requested', 'approved']))->latest()->paginate(25)->withQueryString(),
        ]);
    }

    /**
     * Payout lifecycle with separation of duties: one officer approves, a different one marks it paid.
     * Marking paid posts the ledger entry (Dr user payable / Cr provider clearing).
     */
    public function payoutAction(Request $request, PayoutRequest $payout, Ledger $ledger)
    {
        $data = $request->validate(['action' => 'required|in:approve,pay,reject', 'payout_reference' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500']);
        $actor = $request->user();

        if ($data['action'] === 'approve') {
            abort_unless($payout->status === 'requested', 422);
            $payout->update(['status' => 'approved', 'approved_by' => $actor->id, 'note' => $data['note'] ?? $payout->note]);
        } elseif ($data['action'] === 'reject') {
            abort_unless(in_array($payout->status, ['requested', 'approved'], true), 422);
            abort_if(blank($data['note']), 422, 'Give a reason for rejecting.');
            $payout->update(['status' => 'rejected', 'note' => $data['note']]);
            $payout->user->notify(new PlatformNotification('Payout not processed', 'Your payout request was declined: '.$data['note'], route('app.earnings'), 'banknote'));
        } else {
            abort_unless($payout->status === 'approved', 422, 'Approve the payout first.');
            abort_if($payout->approved_by === $actor->id && $actor->staff_role !== 'super_admin', 422, 'A different officer must confirm payment (dual control).');
            abort_if(blank($data['payout_reference']), 422, 'Enter the provider/bank transfer reference.');
            if ($ledger->balanceOf($payout->user, $payout->currency) < $payout->amount_minor) {
                return back()->with('error', 'The professional’s ledger balance is lower than this payout.');
            }
            DB::transaction(function () use ($payout, $ledger, $actor, $data) {
                $ledger->post('Payout to '.$payout->user->name.' · '.$data['payout_reference'], [
                    [$ledger->payableAccount($payout->user, $payout->currency), $payout->amount_minor, 0],
                    [$ledger->account('platform:cash', $payout->currency), 0, $payout->amount_minor],
                ], $payout, $payout->currency);
                $payout->update(['status' => 'paid', 'paid_by' => $actor->id, 'paid_at' => now(), 'payout_reference' => $data['payout_reference']]);
            });
            $payout->user->notify(new PlatformNotification('Payout sent 💸', money($payout->amount_minor).' is on its way to your bank.', route('app.earnings'), 'banknote'));
        }
        AuditLog::record('payout.'.$data['action'], $payout, $data);

        return back()->with('success', 'Payout '.$payout->fresh()->status.'.');
    }
}
