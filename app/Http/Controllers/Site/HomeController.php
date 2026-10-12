<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\JobPost;
use App\Models\Post;
use App\Models\ProfessionalProfile;
use App\Models\ProfileSkill;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        return view('site.home', [
            'featured' => ProfessionalProfile::discoverable()->with(['user', 'skills'])
                ->whereHas('profileSkills', fn ($q) => $q->whereNotNull('verified_level'))
                ->orderByDesc('last_verified_at')->take(6)->get(),
            'stats' => [
                'talent' => ProfessionalProfile::discoverable()->count(),
                'verified' => ProfileSkill::whereNotNull('verified_level')->count(),
                'assessments' => Assessment::where('status', 'published')->count(),
                'jobs' => JobPost::open()->count(),
            ],
            'skills' => Skill::where('is_active', true)->inRandomOrder()->take(24)->pluck('name'),
            'jobs' => JobPost::open()->with('organization')->latest()->take(3)->get(),
            'projects' => Project::where('status', 'open')->latest()->take(3)->get(),
            'testimonials' => Testimonial::where('is_published', true)->orderBy('position')->take(3)->get(),
            'posts' => Post::published()->latest('published_at')->take(3)->get(),
        ]);
    }
}
