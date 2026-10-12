<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\JobPost;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\ProfessionalProfile;
use App\Models\Project;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->is_published || auth()->user()?->isStaff(), 404);

        return view('site.page', compact('page'));
    }

    public function pricing()
    {
        return view('site.pricing', [
            'plans' => Plan::where('is_active', true)->orderBy('position')->get(),
            'faqs' => Faq::where('is_published', true)->where('audience', 'hirers')->orderBy('position')->get(),
        ]);
    }

    public function how()
    {
        return view('site.how');
    }

    public function faq()
    {
        return view('site.faq', ['faqs' => Faq::where('is_published', true)->orderBy('position')->get()->groupBy('audience')]);
    }

    public function contact()
    {
        return view('site.contact');
    }

    public function sendContact(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'topic' => 'required|in:general,hiring,talent,payments,partnerships,press',
            'message' => 'required|string|min:10|max:4000',
            'website' => 'nullable|max:0', // honeypot
        ]);
        unset($data['website']);
        ContactMessage::create($data);

        User::whereIn('staff_role', ['admin', 'super_admin'])->get()->each->notify(new PlatformNotification(
            'New contact message', "{$data['name']} wrote in about {$data['topic']}.", route('admin.inbox'), 'inbox'
        ));

        return back()->with('success', 'Message received 💌 We’ll get back to you within one working day.');
    }

    public function sitemap()
    {
        $urls = collect([route('home'), route('talent.index'), route('jobs.index'), route('projects.index'), route('skills.index'), route('pricing'), route('how'), route('faq'), route('blog.index'), route('contact')])
            ->merge(Page::where('is_published', true)->get()->map(fn ($p) => route('pages.show', $p)))
            ->merge(Post::published()->get()->map(fn ($p) => route('blog.show', $p)))
            ->merge(ProfessionalProfile::discoverable()->get(['slug'])->map(fn ($p) => route('talent.show', $p)))
            ->merge(JobPost::open()->get(['slug'])->map(fn ($j) => route('jobs.show', $j)))
            ->merge(Project::where('status', 'open')->get(['slug'])->map(fn ($p) => route('projects.show', $p)));

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>'.e($url).'</loc></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml']);
    }
}
