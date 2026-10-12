<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSkill extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
        'verification_expires_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_level !== null
            && ($this->verification_expires_at === null || now()->lt($this->verification_expires_at));
    }
}
