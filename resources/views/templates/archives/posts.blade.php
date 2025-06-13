<x-layouts.app :title="$title ?? 'Posts Archive'" :body-classes="$bodyClasses">
    <x-partials.header />
    <main>
        <div class="archive-header">
            <h1>{{ $title ?? 'Posts Archive' }}</h1>
            @if (isset($archive->description))
                <p class="archive-description">{{ $archive->description }}</p>
            @endif
        </div>

        @if ($posts->count() > 0)
            <div class="posts-grid">
                @foreach ($posts as $post)
                    <article class="post-card">
                        @if ($post->featuredImage)
                            <div class="post-card-image">
                                <a
                                    href="{{ route('cms.single.content', ['lang' => $lang, 'content_type_key' => $post_type, 'content_slug' => $post->slug]) }}">
                                    <img src="{{ $post->featuredImage->url }}"
                                        alt="{{ $post->featuredImage->alt ?? $post->title }}" />
                                </a>
                            </div>
                        @endif

                        <div class="post-card-content">
                            <h2 class="post-card-title">
                                <a
                                    href="{{ route('cms.single.content', ['lang' => $lang, 'content_type_key' => $post_type, 'content_slug' => $post->slug]) }}">
                                    {{ $post->title }}
                                </a>
                            </h2>

                            @if ($post->excerpt)
                                <div class="post-card-excerpt">
                                    {!! Str::limit(strip_tags($post->excerpt), 150) !!}
                                </div>
                            @endif

                            <div class="post-card-meta">
                                @if ($post->published_at)
                                    <time datetime="{{ $post->published_at->toISOString() }}" class="post-card-date">
                                        {{ $post->published_at->format('M j, Y') }}
                                    </time>
                                @endif

                                @if ($post->author)
                                    <span class="post-card-author">
                                        {{ $post->author->name }}
                                    </span>
                                @endif

                                <x-ui.page-views :count="$post->page_views" format="short" class="post-card-views" />

                                <livewire:like-button :content="$post" :lang="$lang" :content-type="$post_type"
                                    size="sm" variant="minimal" :key="'like-button-' . $post->id" />
                            </div>

                            @if ($post->categories->count() > 0)
                                <div class="post-card-categories">
                                    @foreach ($post->categories->take(3) as $category)
                                        <span class="category-tag">{{ $category->title }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($posts->hasPages())
                <div class="pagination-wrapper">
                    {{ $posts->links() }}
                </div>
            @endif
        @else
            <div class="no-posts">
                <p>No posts found.</p>
            </div>
        @endif
    </main>
    <x-partials.footer />
</x-layouts.app>
