<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::published()->when($request->category, fn ($q, $c) => $q->where('category', $c))->latest('published_at')->paginate(9)->withQueryString();
        $categories = Post::published()->whereNotNull('category')->distinct()->pluck('category');

        return view('site.blog.index', compact('posts', 'categories'));
    }

    public function show(Post $post)
    {
        abort_unless(($post->is_published && $post->published_at?->isPast()) || auth()->user()?->isStaff(), 404);
        $more = Post::published()->where('id', '!=', $post->id)->latest('published_at')->take(3)->get();

        return view('site.blog.show', compact('post', 'more'));
    }
}
