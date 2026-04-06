{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Build My Assistant Blog</title>
        <link>{{ url('/blog') }}</link>
        <description>Tips and guides on using AI assistants in your coaching or consulting business.</description>
        <language>en-au</language>
        <atom:link href="{{ url('/blog/feed') }}" rel="self" type="application/rss+xml" />
        @foreach($posts as $post)
        <item>
            <title>{{ htmlspecialchars($post->title) }}</title>
            <link>{{ url('/blog/' . $post->slug) }}</link>
            <guid isPermaLink="true">{{ url('/blog/' . $post->slug) }}</guid>
            <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
            @if($post->excerpt)
            <description>{{ htmlspecialchars($post->excerpt) }}</description>
            @endif
        </item>
        @endforeach
    </channel>
</rss>
