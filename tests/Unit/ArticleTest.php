<?php

use App\Models\Article;

it('uses the blog_posts table', function () {
    $article = new Article;

    expect($article->getTable())->toBe('blog_posts');
});

it('auto-generates a slug from the title', function () {
    $article = Article::create([
        'title' => 'How to Build an AI Assistant',
        'excerpt' => 'A guide.',
        'content' => 'Full content here.',
        'published' => true,
        'published_at' => now(),
    ]);

    expect($article->slug)->toBe('how-to-build-an-ai-assistant');
});

it('scopes published articles', function () {
    Article::create([
        'title' => 'Published Post',
        'excerpt' => 'Visible.',
        'content' => 'Content.',
        'published' => true,
        'published_at' => now()->subDay(),
    ]);

    Article::create([
        'title' => 'Draft Post',
        'excerpt' => 'Hidden.',
        'content' => 'Content.',
        'published' => false,
        'published_at' => null,
    ]);

    expect(Article::published()->count())->toBe(1);
});

it('does not include future-dated articles in published scope', function () {
    Article::create([
        'title' => 'Scheduled Post',
        'excerpt' => 'Future.',
        'content' => 'Content.',
        'published' => true,
        'published_at' => now()->addDay(),
    ]);

    expect(Article::published()->count())->toBe(0);
});

it('casts published to boolean', function () {
    $article = Article::create([
        'title' => 'Test',
        'excerpt' => 'Test.',
        'content' => 'Content.',
        'published' => true,
        'published_at' => now(),
    ]);

    expect($article->published)->toBeBool()->toBeTrue();
});
