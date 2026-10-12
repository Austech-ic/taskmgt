<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    protected $guarded = ['id', 'professional_profile_id', 'verified_at'];

    protected $casts = ['started_on' => 'date', 'ended_on' => 'date', 'is_current' => 'boolean'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }
}
