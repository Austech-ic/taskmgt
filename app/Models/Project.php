<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    protected $guarded = ['id', 'client_id', 'views'];

    protected $casts = ['deadline' => 'date'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class)->latestOfMany();
    }

    public function budgetLabel(): string
    {
        $min = $this->budget_min ? money($this->budget_min, $this->currency, false) : null;
        $max = $this->budget_max ? money($this->budget_max, $this->currency, false) : null;
        $label = $min && $max ? "$min – $max" : ($min ?: $max ?: 'Open budget');

        return $this->contract_type === 'hourly' && ($min || $max) ? "$label/hr" : $label;
    }

    public function clientName(): string
    {
        return $this->organization?->name ?? $this->client->name;
    }
}
