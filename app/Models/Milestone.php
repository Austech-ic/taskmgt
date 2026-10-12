<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Milestone extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_on' => 'date',
        'funded_at' => 'datetime',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class)->latest('version');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }

    public function statusLabel(): string
    {
        return Labels::MILESTONE_STATUS[$this->status] ?? $this->status;
    }

    public function isFunded(): bool
    {
        return in_array($this->status, ['funded', 'submitted', 'revision_requested', 'accepted', 'disputed'], true);
    }
}
