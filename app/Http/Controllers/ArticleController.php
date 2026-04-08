<?php

namespace App\Http\Controllers;

use App\Models\Article;

class ArticleController extends Controller
{
    public function index()
    {
        $posts = Article::published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('articles.index', compact('posts'));
    }

    public function show(string $slug)
    {
        $post = Article::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('articles.show', compact('post'));
    }

    public function feed()
    {
        $posts = Article::published()
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        return response()
            ->view('articles.feed', compact('posts'))
            ->header('Content-Type', 'application/rss+xml');
    }
}
