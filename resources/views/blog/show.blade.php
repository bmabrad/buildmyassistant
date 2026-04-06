<x-layouts.public :title="($post->meta_title ?: $post->title) . ' — Build My Assistant'" :description="$post->meta_description ?: $post->excerpt">

    <div class="section">
        <div class="container" style="max-width: 720px;">
            <article>
                <h1 style="margin-bottom: 8px;">{{ $post->title }}</h1>
                <p style="font-size: 14px; opacity: 0.6; margin-bottom: 32px;">{{ $post->published_at->format('F j, Y') }}</p>

                <div class="blog-content">
                    {!! Str::markdown($post->content) !!}
                </div>
            </article>

            <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--soft-sage);">
                <a href="/blog">&larr; Back to blog</a>
            </div>
        </div>
    </div>

    <style>
        .blog-content h2 { margin-top: 32px; margin-bottom: 12px; }
        .blog-content h3 { margin-top: 24px; margin-bottom: 8px; }
        .blog-content p { margin-bottom: 16px; }
        .blog-content ul, .blog-content ol { margin-bottom: 16px; padding-left: 24px; }
        .blog-content li { margin-bottom: 4px; }
        .blog-content blockquote {
            border-left: 3px solid var(--sage-accent);
            padding-left: 16px;
            margin: 16px 0;
            font-style: italic;
            opacity: 0.85;
        }
        .blog-content code {
            background: var(--off-white);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
        }
        .blog-content pre {
            background: var(--off-white);
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin-bottom: 16px;
        }
        .blog-content pre code { background: none; padding: 0; }
        .blog-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 16px 0; }
        .blog-content a { color: var(--sage-accent); text-decoration: underline; }
    </style>

</x-layouts.public>
