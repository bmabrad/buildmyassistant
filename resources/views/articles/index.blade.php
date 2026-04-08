<x-layouts.public :title="'Articles — Build My Assistant'" :description="'Tips and guides on using AI assistants in your business.'">

    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <div class="text-center mb-10">
                <h1 class="text-4xl font-medium text-slate leading-tight mb-3">Articles</h1>
                <p>Tips and guides on using AI assistants in your business.</p>
            </div>

            @if($posts->count())
                <div class="space-y-8">
                    @foreach($posts as $post)
                        <article class="border-b border-soft-sage pb-8">
                            @if($post->featured_image)
                                <a href="/articles/{{ $post->slug }}" class="block mb-4">
                                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-48 object-cover rounded-lg">
                                </a>
                            @endif
                            <a href="/articles/{{ $post->slug }}" class="no-underline">
                                <h2 class="text-2xl font-medium text-slate leading-tight mb-2">{{ $post->title }}</h2>
                            </a>
                            <p class="text-sm text-mid-blue/60 mb-3">{{ $post->published_at->format('M j, Y') }}</p>
                            @if($post->excerpt)
                                <p class="mb-3">{{ $post->excerpt }}</p>
                            @endif
                            <a href="/articles/{{ $post->slug }}" class="text-sage font-medium text-sm">Read more &rarr;</a>
                        </article>
                    @endforeach
                </div>

                <div class="mt-10 text-center">
                    {{ $posts->links() }}
                </div>
            @else
                <div class="text-center py-10">
                    <p>No posts yet. Check back soon.</p>
                </div>
            @endif
        </div>
    </section>

</x-layouts.public>
