<?php

use App\Models\Article;

it('loads the homepage', function () {
    $this->get('/')->assertStatus(200)->assertSee('Build My Assistant');
});

it('loads the about page', function () {
    $this->get('/about')->assertStatus(200)->assertSee('About');
});

it('loads the contact page', function () {
    $this->get('/contact')->assertStatus(200)->assertSee('Get in touch');
});

it('loads the privacy page', function () {
    $this->get('/privacy')->assertStatus(200)->assertSee('Privacy');
});

it('loads the terms page', function () {
    $this->get('/terms')->assertStatus(200)->assertSee('Terms');
});

it('loads the launchpad sales page', function () {
    $this->get('/launchpad')->assertStatus(200)->assertSee('Launchpad');
});

it('has nav links to all main pages', function () {
    $response = $this->get('/');

    $response->assertSee('href="/launchpad"', false);
    $response->assertSee('href="/blog"', false);
    $response->assertSee('href="/about"', false);
    $response->assertSee('href="/contact"', false);
});

it('has footer links to privacy and terms', function () {
    $response = $this->get('/');

    $response->assertSee('href="/privacy"', false);
    $response->assertSee('href="/terms"', false);
});

it('loads the blog index page', function () {
    $this->get('/blog')->assertStatus(200)->assertSee('Blog');
});

it('shows published blog posts on the index', function () {
    Article::create([
        'title' => 'Test Published Post',
        'slug' => 'test-published-post',
        'content' => 'This is test content.',
        'published' => true,
        'published_at' => now()->subDay(),
    ]);

    Article::create([
        'title' => 'Draft Post',
        'slug' => 'draft-post',
        'content' => 'This is a draft.',
        'published' => false,
        'published_at' => null,
    ]);

    $this->get('/blog')
        ->assertSee('Test Published Post')
        ->assertDontSee('Draft Post');
});

it('shows a single blog post by slug', function () {
    Article::create([
        'title' => 'My Blog Post',
        'slug' => 'my-blog-post',
        'content' => 'Full blog content here.',
        'published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get('/blog/my-blog-post')
        ->assertStatus(200)
        ->assertSee('My Blog Post')
        ->assertSee('Full blog content here.');
});

it('returns 404 for unpublished blog post', function () {
    Article::create([
        'title' => 'Hidden Post',
        'slug' => 'hidden-post',
        'content' => 'Secret.',
        'published' => false,
        'published_at' => null,
    ]);

    $this->get('/blog/hidden-post')->assertStatus(404);
});

it('returns 404 for future blog post', function () {
    Article::create([
        'title' => 'Future Post',
        'slug' => 'future-post',
        'content' => 'Not yet.',
        'published' => true,
        'published_at' => now()->addWeek(),
    ]);

    $this->get('/blog/future-post')->assertStatus(404);
});

it('serves the blog RSS feed', function () {
    Article::create([
        'title' => 'RSS Post',
        'slug' => 'rss-post',
        'content' => 'Feed content.',
        'published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get('/blog/feed')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/rss+xml')
        ->assertSee('RSS Post');
});

it('has the checkout button on the sales page', function () {
    $this->get('/launchpad')
        ->assertSee('checkout', false);
});
