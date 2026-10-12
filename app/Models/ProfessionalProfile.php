<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfessionalProfile extends Model
{
    protected $guarded = ['id', 'user_id', 'status', 'approved_at', 'identity_verified_at', 'portfolio_verified_at', 'last_verified_at', 'review_notes'];

    protected $casts = [
        'show_rate' => 'boolean',
        'allow_direct_contact' => 'boolean',
        'is_public' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'identity_verified_at' => 'datetime',
        'portfolio_verified_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'profile_skills')
            ->withPivot(['id', 'self_level', 'years', 'is_primary', 'verified_level', 'verified_score', 'verified_at', 'verification_expires_at'])
            ->withTimestamps();
    }

    public function profileSkills(): HasMany
    {
        return $this->hasMany(ProfileSkill::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->orderByDesc('is_current')->orderByDesc('started_on');
    }

    public function portfolio(): HasMany
    {
        return $this->hasMany(PortfolioItem::class)->orderBy('position');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class)->orderByDesc('issued_on');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /** Profiles that may appear in search: approved (or conditionally approved) and public. */
    public function scopeDiscoverable(Builder $q): Builder
    {
        return $q->whereIn('status', ['approved', 'conditionally_approved'])
            ->where('is_public', true)
            ->whereHas('user', fn ($u) => $u->where('status', 'active'));
    }

    public function isDiscoverable(): bool
    {
        return in_array($this->status, ['approved', 'conditionally_approved'], true) && $this->is_public;
    }

    public function verifiedSkills()
    {
        return $this->skills->filter(fn ($s) => $s->pivot->verified_level !== null
            && ($s->pivot->verification_expires_at === null || now()->lt($s->pivot->verification_expires_at)));
    }

    public function topVerifiedLevel(): ?int
    {
        return $this->verifiedSkills()->max(fn ($s) => $s->pivot->verified_level);
    }

    public function statusLabel(): string
    {
        return Labels::PROFILE_STATUS[$this->status] ?? ucfirst($this->status);
    }

    public function availabilityLabel(): string
    {
        return Labels::AVAILABILITY[$this->availability] ?? $this->availability;
    }

    public function location(): string
    {
        return collect([$this->city, $this->country])->filter()->implode(', ');
    }

    public function rateLabel(): ?string
    {
        if (! $this->rate_min && ! $this->rate_max) {
            return null;
        }
        $unit = ['hour' => '/hr', 'month' => '/mo', 'project' => '/project'][$this->rate_unit] ?? '';
        $min = $this->rate_min ? money($this->rate_min, $this->currency, false) : null;
        $max = $this->rate_max ? money($this->rate_max, $this->currency, false) : null;

        return trim(($min && $max ? "$min – $max" : ($min ?: $max)).$unit);
    }

    /** Onboarding checklist — what's still needed before review. */
    public function checklist(): array
    {
        $this->loadMissing(['skills', 'experiences', 'portfolio']);
        $primary = $this->skills->firstWhere('pivot.is_primary', true);

        return [
            ['key' => 'basics', 'label' => 'Add a headline & bio', 'done' => filled($this->headline) && filled($this->bio), 'route' => 'app.profile.edit'],
            ['key' => 'skills', 'label' => 'Claim at least 3 skills from the taxonomy', 'done' => $this->skills->count() >= 3, 'route' => 'app.profile.skills'],
            ['key' => 'primary', 'label' => 'Mark your primary skill', 'done' => (bool) $primary, 'route' => 'app.profile.skills'],
            ['key' => 'experience', 'label' => 'Add work history or education', 'done' => $this->experiences->isNotEmpty(), 'route' => 'app.profile.experience'],
            ['key' => 'portfolio', 'label' => 'Show off one portfolio project', 'done' => $this->portfolio->isNotEmpty(), 'route' => 'app.profile.portfolio'],
            ['key' => 'assessment', 'label' => 'Pass the assessment for your primary skill', 'done' => $primary && $primary->pivot->verified_level !== null, 'route' => 'app.assessments.index'],
        ];
    }

    public function completion(): int
    {
        $list = $this->checklist();

        return (int) round(collect($list)->where('done', true)->count() / count($list) * 100);
    }

    public function reviewsReceived()
    {
        return Review::where('reviewee_id', $this->user_id)->where('status', 'published');
    }

    public function stats(): array
    {
        $contracts = Contract::where('professional_id', $this->user_id);
        $completed = (clone $contracts)->where('status', 'completed')->count();
        $reviews = $this->reviewsReceived();
        $onTimeTotal = (clone $reviews)->whereNotNull('on_time')->count();
        $onTime = (clone $reviews)->where('on_time', true)->count();

        return [
            'completed' => $completed,
            'rating' => round((float) (clone $reviews)->avg('overall'), 1),
            'reviews' => (clone $reviews)->count(),
            'on_time' => $onTimeTotal ? (int) round($onTime / $onTimeTotal * 100) : null,
        ];
    }
}
