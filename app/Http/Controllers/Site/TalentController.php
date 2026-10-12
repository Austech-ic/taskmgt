<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ProfessionalProfile;
use App\Models\Review;
use App\Models\SavedSearch;
use App\Models\SkillCategory;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Copilot;
use App\Services\TalentSearch;
use App\Support\Labels;
use Illuminate\Http\Request;

class TalentController extends Controller
{
    public function index(Request $request, TalentSearch $search)
    {
        $filters = $request->only(TalentSearch::FILTERS);
        $org = $request->user()?->organization();

        return view('site.talent.index', [
            'results' => $search->search($filters),
            'filters' => $filters,
            'categories' => SkillCategory::with('skills')->orderBy('position')->get(),
            'org' => $org,
            'shortlists' => $org?->shortlists()->latest()->get() ?? collect(),
            'savedSearches' => $request->user() ? SavedSearch::where('user_id', $request->user()->id)->latest()->get() : collect(),
            'aiEnabled' => app(Copilot::class)->enabled(),
        ]);
    }

    /** Natural-language search → structured filters → redirect to the normal results page. */
    public function aiSearch(Request $request, Copilot $copilot)
    {
        $data = $request->validate(['prompt' => 'required|string|max:400']);

        try {
            $parsed = $copilot->parseTalentQuery($data['prompt']);
        } catch (AiUnavailable $e) {
            return redirect()->route('talent.index', ['q' => $data['prompt']])->with('info', $e->getMessage().' Showing keyword results instead.');
        }

        $query = array_filter([
            'q' => $parsed['keywords'] ?? null,
            'skills' => ! empty($parsed['skills']) ? implode(',', $parsed['skills']) : null,
            'min_level' => ($parsed['min_level'] ?? 0) ?: null,
            'location' => $parsed['location'] ?? null,
            'availability' => $parsed['availability'] ?? null,
            'arrangement' => $parsed['arrangement'] ?? null,
            'min_years' => ($parsed['min_years'] ?? 0) ?: null,
        ]);
        // If structured filters were found, the free-text keywords usually over-constrain the search.
        if (count($query) > 1) {
            unset($query['q']);
        }

        return redirect()->route('talent.index', $query)->with('success', '✨ '.($parsed['summary'] ?? 'Filters applied.'));
    }

    public function show(Request $request, ProfessionalProfile $profile)
    {
        $viewer = $request->user();
        $isOwner = $viewer && $viewer->id === $profile->user_id;
        abort_unless($profile->isDiscoverable() || $isOwner || $viewer?->hasStaffRole('admin', 'reviewer'), 404);

        if (! $isOwner) {
            $profile->increment('profile_views');
        }

        $profile->load(['user', 'skills.category', 'experiences', 'portfolio', 'certifications']);
        $org = $viewer?->organization();

        return view('site.talent.show', [
            'profile' => $profile,
            'isOwner' => $isOwner,
            'stats' => $profile->stats(),
            'reviews' => Review::where('reviewee_id', $profile->user_id)->where('status', 'published')->with('reviewer', 'contract')->latest()->take(6)->get(),
            'org' => $org,
            'unlocked' => $org?->hasUnlocked($profile) ?? false,
            'shortlists' => $org?->shortlists()->latest()->get() ?? collect(),
            'levels' => Labels::LEVELS,
        ]);
    }
}
