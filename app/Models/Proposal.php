<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['milestones' => 'array'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }
}
