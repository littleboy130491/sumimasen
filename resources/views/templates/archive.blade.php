<x-sumimasen-cms::layouts.app :title="$title ?? 'Archive'" :body-classes="$bodyClasses">
    <x-sumimasen-cms::partials.header />
    <main>
        <h1>{{ $archive->name ?? 'Archive' }}</h1>
        @if (!empty($archive->description))
            <p>{{ $archive->description }}</p>
        @endif

        {{-- Loop through posts --}}
        @forelse ($posts as $post)
            <article>
                <h2>{{ $post->title ?? 'Untitled' }}</h2>
                {{-- Display excerpt or content --}}
                <p>{{ $post->excerpt }}</p>
                <a href="{{ url($lang . '/' . ($post->post_type ?? 'post') . '/' . $post->slug) }}">Read More</a>
            </article>
        @empty
            <p>No content found for this archive.</p>
        @endforelse

        {{-- Pagination links --}}
        @if (method_exists($posts, 'links'))
            {{ $posts->links() }}
        @endif

    </main>
    <x-sumimasen-cms::partials.footer />
</x-sumimasen-cms::layouts.app>
