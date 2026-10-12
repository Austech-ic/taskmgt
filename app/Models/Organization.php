<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Organization extends Model
{
    protected $guarded = ['id', 'owner_id', 'verified_at', 'unlock_credits'];

    protected $casts = ['verified_at' => 'datetime'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_members')->withPivot('role')->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(ContactUnlock::class);
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('current_period_end', '>', now())
            ->latest('id')->first();
    }

    public function hasUnlocked(ProfessionalProfile $profile): bool
    {
        return $this->unlocks()->where('professional_profile_id', $profile->id)->exists();
    }

    public function unlocksAvailable(): int
    {
        return (int) $this->unlock_credits + (int) optional($this->activeSubscription())->unlocks_remaining;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
