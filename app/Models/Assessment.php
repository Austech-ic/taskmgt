<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['requires_review' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function activeQuestions(): HasMany
    {
        return $this->questions()->where('is_active', true);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** When can this user next attempt? Null = now. */
    public function nextEligibleAt(User $user): ?Carbon
    {
        $last = $this->attempts()->where('user_id', $user->id)->latest('id')->first();
        if (! $last) {
            return null;
        }
        if (in_array($last->status, ['in_progress', 'submitted', 'under_review'], true)) {
            return now()->addYears(10); // finish/await the current attempt first
        }
        if ($last->status === 'passed') {
            $profileSkill = $user->profile?->profileSkills()->where('skill_id', $this->skill_id)->first();
            if ($profileSkill && $profileSkill->verification_expires_at) {
                return $profileSkill->verification_expires_at->copy()->subMonth()->isPast() ? null : $profileSkill->verification_expires_at->copy()->subMonth();
            }

            return now()->addYears(10);
        }
        $eligible = ($last->submitted_at ?? $last->updated_at)->copy()->addDays($this->retake_days);

        return $eligible->isPast() ? null : $eligible;
    }
}
