<x-sumimasen-cms::layouts.app :title="$content->title ?? 'Default Page'" :body-classes="$bodyClasses">
    <x-sumimasen-cms::partials.header />
    <main>
        test
        <h1>{{ $content->title ?? 'Default Page' }}</h1>

        {{-- Content goes here --}}
        <div class="content">
            {!! $content->content ?? 'No content available.' !!}
        </div>

    </main>
    <x-sumimasen-cms::partials.footer />
</x-sumimasen-cms::layouts.app>
