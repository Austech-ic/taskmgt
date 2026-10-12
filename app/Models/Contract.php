<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'client_accepted_at' => 'datetime',
        'professional_accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('position');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function involves(User $user): bool
    {
        return in_array($user->id, [$this->client_id, $this->professional_id], true);
    }

    public function commissionPercent(): string
    {
        return rtrim(rtrim(number_format($this->commission_bps / 100, 2), '0'), '.').'%';
    }

    public function statusLabel(): string
    {
        return Labels::CONTRACT_STATUS[$this->status] ?? $this->status;
    }

    public function releasedTotal(): int
    {
        return (int) $this->milestones->where('status', 'released')->sum('amount_minor');
    }

    public function fundedTotal(): int
    {
        return (int) $this->milestones->whereIn('status', ['funded', 'submitted', 'revision_requested', 'accepted', 'released', 'disputed'])->sum('amount_minor');
    }
}
