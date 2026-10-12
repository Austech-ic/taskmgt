<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerAccount extends Model
{
    protected $guarded = ['id'];

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Natural balance: debit-normal for assets/expenses, credit-normal for liabilities/revenue. */
    public function balance(): int
    {
        $debits = (int) $this->entries()->sum('debit_minor');
        $credits = (int) $this->entries()->sum('credit_minor');

        return in_array($this->type, ['asset', 'expense'], true) ? $debits - $credits : $credits - $debits;
    }
}
