<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    public const STAGES = [
        'new' => 'New',
        'shortlisted' => 'Shortlisted',
        'contacted' => 'Contacted',
        'interview' => 'Interview',
        'offer' => 'Offer',
        'hired' => 'Hired',
        'closed' => 'Closed',
    ];

    protected $guarded = ['id'];

    protected $casts = ['withdrawn_at' => 'datetime'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPost::class, 'job_post_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class)->latest();
    }

    public function stageLabel(): string
    {
        return $this->withdrawn_at ? 'Withdrawn' : (self::STAGES[$this->stage] ?? $this->stage);
    }
}
