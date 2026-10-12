<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    protected $guarded = ['id', 'professional_profile_id', 'verified_at'];

    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date', 'verified_at' => 'datetime'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }
}
