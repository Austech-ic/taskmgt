<?php

namespace App\Services;

use App\Models\ProfessionalProfile;
use App\Models\Skill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Talent directory search. Ranking is evidence-based (verified level, completed work,
 * recency of verification) — never paid placement.
 */
class TalentSearch
{
    public const FILTERS = ['q', 'skills', 'category', 'min_level', 'verified_only', 'location', 'availability', 'arrangement', 'min_years', 'max_rate', 'sort'];

    public function search(array $f, int $perPage = 12): LengthAwarePaginator
    {
        return $this->query($f)->paginate($perPage)->withQueryString();
    }

    public function query(array $f): Builder
    {
        $skillIds = $this->resolveSkills($f);
        $minLevel = (int) ($f['min_level'] ?? 0);

        $q = ProfessionalProfile::discoverable()->with(['user', 'skills']);

        if ($term = trim((string) ($f['q'] ?? ''))) {
            $matchingSkills = Skill::where('name', 'like', "%{$term}%")->orWhere('synonyms', 'like', "%{$term}%")->pluck('id');
            $q->where(function (Builder $w) use ($term, $matchingSkills) {
                $w->where('headline', 'like', "%{$term}%")
                    ->orWhere('bio', 'like', "%{$term}%")
                    ->orWhere('primary_role', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"));
                if ($matchingSkills->isNotEmpty()) {
                    $w->orWhereHas('skills', fn ($s) => $s->whereIn('skills.id', $matchingSkills));
                }
            });
        }

        // Every requested skill must be present; with a level filter or "verified only" it must be assessment-verified.
        foreach ($skillIds as $skillId) {
            $q->whereHas('profileSkills', function (Builder $ps) use ($skillId, $minLevel, $f) {
                $ps->where('skill_id', $skillId);
                if ($minLevel > 0 || ! empty($f['verified_only'])) {
                    $ps->where('verified_level', '>=', max(1, $minLevel))
                        ->where(fn ($e) => $e->whereNull('verification_expires_at')->orWhere('verification_expires_at', '>', now()));
                }
            });
        }

        if (empty($skillIds) && ($minLevel > 0 || ! empty($f['verified_only']))) {
            $q->whereHas('profileSkills', fn ($ps) => $ps->where('verified_level', '>=', max(1, $minLevel))
                ->where(fn ($e) => $e->whereNull('verification_expires_at')->orWhere('verification_expires_at', '>', now())));
        }

        if (! empty($f['category'])) {
            $q->whereHas('skills', fn ($s) => $s->where('skill_category_id', $f['category']));
        }
        if ($loc = trim((string) ($f['location'] ?? ''))) {
            $q->where(fn ($w) => $w->where('city', 'like', "%{$loc}%")->orWhere('country', 'like', "%{$loc}%")->orWhere('timezone', 'like', "%{$loc}%"));
        }
        if (! empty($f['availability'])) {
            $q->where('availability', $f['availability']);
        } else {
            $q->where('availability', '!=', 'unavailable');
        }
        if (! empty($f['arrangement'])) {
            $q->whereIn('work_arrangement', [$f['arrangement'], 'any']);
        }
        if (! empty($f['min_years'])) {
            $q->where('years_experience', '>=', (int) $f['min_years']);
        }
        if (! empty($f['max_rate']) && ($max = to_minor($f['max_rate']))) {
            $q->where('show_rate', true)->where('rate_min', '<=', $max);
        }

        $q->withMax(['profileSkills as best_level' => fn ($ps) => $ps->whereNotNull('verified_level')], 'verified_level')
            ->withCount(['profileSkills as verified_count' => fn ($ps) => $ps->whereNotNull('verified_level')]);

        return match ($f['sort'] ?? 'relevance') {
            'newest' => $q->latest('approved_at'),
            'experience' => $q->orderByDesc('years_experience'),
            'recently_verified' => $q->orderByDesc('last_verified_at'),
            default => $q->orderByDesc('best_level')->orderByDesc('verified_count')->orderByDesc('last_verified_at'),
        };
    }

    /** @return int[] */
    public function resolveSkills(array $f): array
    {
        $skills = $f['skills'] ?? [];
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', explode(',', $skills)));
        }
        if (empty($skills)) {
            return [];
        }

        return Skill::where(fn ($q) => $q->whereIn('id', array_filter($skills, 'is_numeric'))->orWhereIn('name', $skills)->orWhereIn('slug', $skills))
            ->pluck('id')->all();
    }
}
