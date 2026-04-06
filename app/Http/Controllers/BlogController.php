<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('blog.index', compact('posts'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('blog.show', compact('post'));
    }

    public function feed()
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        return response()
            ->view('blog.feed', compact('posts'))
            ->header('Content-Type', 'application/rss+xml');
    }
}
