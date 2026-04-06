<x-layouts.public :title="'Blog — Build My Assistant'" :description="'Tips and guides on using AI assistants in your coaching or consulting business.'">

    <div class="section">
        <div class="container">
            <div class="section-header">
                <h1>Blog</h1>
                <p>Tips and guides on using AI assistants in your coaching or consulting business.</p>
            </div>

            @if($posts->count())
                <div style="display: grid; gap: 32px; max-width: 720px; margin: 0 auto;">
                    @foreach($posts as $post)
                        <article style="border-bottom: 1px solid var(--soft-sage); padding-bottom: 32px;">
                            <a href="/blog/{{ $post->slug }}" style="text-decoration: none;">
                                <h2 style="margin-bottom: 8px; color: var(--deep-slate);">{{ $post->title }}</h2>
                            </a>
                            <p style="font-size: 14px; opacity: 0.6; margin-bottom: 12px;">{{ $post->published_at->format('M j, Y') }}</p>
                            @if($post->excerpt)
                                <p style="margin-bottom: 12px;">{{ $post->excerpt }}</p>
                            @endif
                            <a href="/blog/{{ $post->slug }}" style="font-size: 15px; font-weight: 500;">Read more &rarr;</a>
                        </article>
                    @endforeach
                </div>

                <div style="margin-top: 40px; text-align: center;">
                    {{ $posts->links() }}
                </div>
            @else
                <div style="text-align: center; padding: 40px 0;">
                    <p>No posts yet. Check back soon.</p>
                </div>
            @endif
        </div>
    </div>

</x-layouts.public>
