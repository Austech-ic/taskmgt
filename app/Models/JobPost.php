<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPost extends Model
{
    protected $guarded = ['id', 'organization_id', 'posted_by', 'views'];

    protected $casts = [
        'deadline' => 'date',
        'show_salary' => 'boolean',
        'require_verified' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('is_required');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', 'open')->where(fn ($d) => $d->whereNull('deadline')->orWhere('deadline', '>=', now()->toDateString()));
    }

    public function salaryLabel(): ?string
    {
        if (! $this->show_salary || (! $this->salary_min && ! $this->salary_max)) {
            return null;
        }
        $min = $this->salary_min ? money($this->salary_min, $this->currency, false) : null;
        $max = $this->salary_max ? money($this->salary_max, $this->currency, false) : null;

        return ($min && $max ? "$min – $max" : ($min ?: $max)).'/mo';
    }

    public function typeLabel(): string
    {
        return Labels::EMPLOYMENT_TYPES[$this->employment_type] ?? $this->employment_type;
    }
}
