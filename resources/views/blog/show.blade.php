<x-layouts.public :title="($post->meta_title ?: $post->title) . ' — Build My Assistant'" :description="$post->meta_description ?: $post->excerpt">

    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <article>
                <h1 class="text-4xl font-medium text-slate leading-tight mb-2">{{ $post->title }}</h1>
                <p class="text-sm text-mid-blue/60 mb-8">{{ $post->published_at->format('F j, Y') }}</p>

                <div class="blog-content text-mid-blue leading-[1.7]">
                    {!! Str::markdown($post->content) !!}
                </div>
            </article>

            <div class="mt-12 pt-6 border-t border-soft-sage">
                <a href="/blog" class="text-sage hover:underline">&larr; Back to blog</a>
            </div>
        </div>
    </section>

    <style>
        .blog-content h2 { @apply text-2xl font-medium text-slate mt-8 mb-3; }
        .blog-content h3 { @apply text-lg font-medium text-slate mt-6 mb-2; }
        .blog-content p { @apply mb-4; }
        .blog-content ul, .blog-content ol { @apply mb-4 pl-6; }
        .blog-content li { @apply mb-1; }
        .blog-content blockquote { @apply border-l-3 border-sage pl-4 my-4 italic opacity-85; }
        .blog-content code { @apply bg-off-white px-1.5 py-0.5 rounded text-sm; }
        .blog-content pre { @apply bg-off-white p-4 rounded-lg overflow-x-auto mb-4; }
        .blog-content pre code { @apply bg-transparent p-0; }
        .blog-content img { @apply max-w-full h-auto rounded-lg my-4; }
        .blog-content a { @apply text-sage underline; }
    </style>

</x-layouts.public>
