@php
    use Illuminate\Support\Str;
@endphp
<x-sumimasen-cms::layouts.app :title="$content->title ?? 'Post'" :body-classes="$bodyClasses">
    <x-sumimasen-cms::partials.header />
    <main>
        <article class="post-single">
            <header class="post-header">
                <h1 class="post-title">{{ $content->title ?? 'Untitled Post' }}</h1>

                <div class="post-meta">
                    @if ($content->published_at)
                        <time datetime="{{ $content->published_at->toISOString() }}" class="post-date">
                            {{ $content->published_at->format('F j, Y') }}
                        </time>
                    @endif

                    @if ($content->author)
                        <span class="post-author">
                            by {{ $content->author->name }}
                        </span>
                    @endif
                    <x-sumimasen-cms::ui.page-views :count="$content->custom_fields['page_views']" format="long" class="post-views" />

                    <span class="post-likes">
                        {{ $content->page_likes }} {{ Str::plural('like', $content->page_likes) }}
                    </span>
                </div>
            </header>

            @if ($content->featuredImage)
                <div class="post-featured-image">
                    <img src="{{ $content->featuredImage->url }}"
                        alt="{{ $content->featuredImage->alt ?? $content->title }}" />
                </div>
            @endif

            @if ($content->excerpt)
                <div class="post-excerpt">
                    {!! $content->excerpt !!}
                </div>
            @endif

            <div class="post-content">
                {!! $content->content !!}
            </div>
            <x-sumimasen-cms::ui.behold-ig-feed />

            {{-- Like button section --}}
            <div class="post-actions">
                <livewire:like-button :content="$content" :lang="$lang" :content-type="$content_type" />
            </div>

            @if ($content->categories->count() > 0)
                <div class="post-categories">
                    <strong>Categories:</strong>
                    @foreach ($content->categories as $category)
                        <a href="#" class="category-link">{{ $category->title }}</a>
                        @if (!$loop->last)
                            ,
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($content->tags->count() > 0)
                <div class="post-tags">
                    <strong>Tags:</strong>
                    @foreach ($content->tags as $tag)
                        <a href="#" class="tag-link">#{{ $tag->title }}</a>
                        @if (!$loop->last)
                        @endif
                    @endforeach
                </div>
            @endif
        </article>

        {{-- Comments section --}}
        @if ($content->comments->count() > 0)
            <section class="comments-section">
                <h3>Comments ({{ $content->comments->count() }})</h3>
                @foreach ($content->comments as $comment)
                    <div class="comment">
                        <div class="comment-meta">
                            <strong>{{ $comment->author_name }}</strong>
                            <time datetime="{{ $comment->created_at->toISOString() }}">
                                {{ $comment->created_at->format('F j, Y \a\t g:i A') }}
                            </time>
                        </div>
                        <div class="comment-content">
                            {!! $comment->content !!}
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    </main>
    <x-sumimasen-cms::partials.footer />
</x-sumimasen-cms::layouts.app>
