<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\SkillCategory;
use App\Support\Labels;

class SkillController extends Controller
{
    public function index()
    {
        return view('site.skills', [
            'assessments' => Assessment::where('status', 'published')->with('skill.category')->withCount(['attempts as passed_count' => fn ($q) => $q->where('status', 'passed')])->get()->groupBy(fn ($a) => $a->skill->category->name),
            'categories' => SkillCategory::with('skills')->orderBy('position')->get(),
            'levels' => Labels::LEVELS,
        ]);
    }
}
