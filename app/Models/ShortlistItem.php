<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortlistItem extends Model
{
    protected $guarded = ['id'];

    public function shortlist(): BelongsTo
    {
        return $this->belongsTo(Shortlist::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }
}
