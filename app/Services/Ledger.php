<?php

namespace App\Services;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Double-entry ledger. Every journal must balance (sum of debits == sum of credits).
 * Entries are append-only; corrections are made with reversing journals.
 */
class Ledger
{
    public const ACCOUNTS = [
        'platform:cash' => ['Payment provider clearing', 'asset'],
        'escrow:held' => ['Client funds held for milestones', 'liability'],
        'revenue:commission' => ['Marketplace commission', 'revenue'],
        'revenue:unlocks' => ['Contact unlock sales', 'revenue'],
        'revenue:subscriptions' => ['Recruiter subscriptions', 'revenue'],
    ];

    public function account(string $code, string $currency = 'NGN'): LedgerAccount
    {
        if (! isset(self::ACCOUNTS[$code])) {
            throw new InvalidArgumentException("Unknown ledger account [$code].");
        }
        [$name, $type] = self::ACCOUNTS[$code];

        return LedgerAccount::firstOrCreate(['code' => $code.':'.$currency], ['name' => $name, 'type' => $type, 'currency' => $currency]);
    }

    public function payableAccount(User $user, string $currency = 'NGN'): LedgerAccount
    {
        return LedgerAccount::firstOrCreate(
            ['code' => "user:{$user->id}:payable:{$currency}"],
            ['name' => "Earnings payable · {$user->name}", 'type' => 'liability', 'user_id' => $user->id, 'currency' => $currency]
        );
    }

    /**
     * @param  array<int, array{0: LedgerAccount, 1: int, 2: int}>  $lines  [account, debit, credit]
     */
    public function post(string $memo, array $lines, ?Model $source = null, string $currency = 'NGN'): string
    {
        $debits = array_sum(array_column($lines, 1));
        $credits = array_sum(array_column($lines, 2));
        if ($debits !== $credits || $debits === 0) {
            throw new InvalidArgumentException("Unbalanced journal: debits $debits ≠ credits $credits.");
        }

        $journal = (string) Str::uuid();
        DB::transaction(function () use ($lines, $memo, $source, $journal, $currency) {
            foreach ($lines as [$account, $debit, $credit]) {
                if ($debit === 0 && $credit === 0) {
                    continue;
                }
                LedgerEntry::create([
                    'journal_id' => $journal,
                    'ledger_account_id' => $account->id,
                    'debit_minor' => $debit,
                    'credit_minor' => $credit,
                    'currency' => $currency,
                    'memo' => $memo,
                    'source_type' => $source?->getMorphClass(),
                    'source_id' => $source?->getKey(),
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return $journal;
    }

    public function balanceOf(User $user, string $currency = 'NGN'): int
    {
        return $this->payableAccount($user, $currency)->balance();
    }

    public function isBalanced(): bool
    {
        return (int) LedgerEntry::sum('debit_minor') === (int) LedgerEntry::sum('credit_minor');
    }
}
