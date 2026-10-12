<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Append-only: corrections must be made with reversing entries.
        static::updating(fn () => throw new LogicException('Ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Ledger entries are immutable.'));
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
