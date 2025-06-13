<x-layouts.app :title="$content->title ?? 'Default Page'" :body-classes="$bodyClasses">
    <x-partials.header />
    <main>
        test
        <h1>{{ $content->title ?? 'Default Page' }}</h1>

        {{-- Content goes here --}}
        <div class="content">
            {!! $content->content ?? 'No content available.' !!}
        </div>

    </main>
    <x-partials.footer />
</x-layouts.app>
