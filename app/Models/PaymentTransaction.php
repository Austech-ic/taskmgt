<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTransaction extends Model
{
    public const PURPOSES = [
        'milestone_funding' => 'Milestone funding',
        'contact_credits' => 'Contact unlock credits',
        'subscription' => 'Recruiter subscription',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'provider_payload' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function purposeLabel(): string
    {
        return self::PURPOSES[$this->purpose] ?? $this->purpose;
    }
}
