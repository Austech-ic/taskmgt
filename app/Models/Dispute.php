<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    public const REASONS = [
        'quality' => 'Deliverable doesn’t meet acceptance criteria',
        'scope' => 'Scope disagreement',
        'deadline' => 'Missed deadline',
        'non_response' => 'Other party is unresponsive',
        'payment' => 'Payment issue',
        'other' => 'Something else',
    ];

    protected $guarded = ['id'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function raiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
