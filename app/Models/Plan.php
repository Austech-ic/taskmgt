<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'features' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];
}
