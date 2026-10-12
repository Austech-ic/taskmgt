<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class, 'skill_category_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function publishedAssessment(): ?Assessment
    {
        return $this->assessments->firstWhere('status', 'published')
            ?? $this->assessments()->where('status', 'published')->first();
    }
}
