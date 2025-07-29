{{-- Basic usage example --}}
<x-sumimasen-cms-loop-grid :query="$posts">
    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold">{{ $item->title }}</h3>
        <p class="text-gray-600 mt-2">{{ $item->excerpt }}</p>
    </div>
</x-sumimasen-cms-loop-grid>

{{-- Advanced usage with custom responsive columns and pagination --}}
<x-sumimasen-cms-loop-grid 
    :query="$posts" 
    sm="2" 
    md="3" 
    lg="6"
    gap="4"
    :pagination="true"
    :attributes="['id' => 'posts-grid', 'class' => 'my-custom-class']">
    
    <article class="bg-gray-50 rounded-lg overflow-hidden">
        <img src="{{ $item->featured_image }}" alt="{{ $item->title }}" class="w-full h-48 object-cover">
        <div class="p-4">
            <h2 class="text-xl font-bold mb-2">{{ $item->title }}</h2>
            <p class="text-gray-700">{{ $item->excerpt }}</p>
            <a href="{{ route('post.show', $item->slug) }}" class="text-blue-600 hover:underline mt-3 inline-block">
                Read more
            </a>
        </div>
    </article>
</x-sumimasen-cms-loop-grid>

{{-- Usage with collection instead of query builder --}}
@php
$items = collect([
    (object)['title' => 'Item 1', 'description' => 'Description 1'],
    (object)['title' => 'Item 2', 'description' => 'Description 2'],
    (object)['title' => 'Item 3', 'description' => 'Description 3'],
]);
@endphp

<x-sumimasen-cms-loop-grid :query="$items" lg="3" gap="6">
    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
        <h4 class="font-medium text-gray-900">{{ $item->title }}</h4>
        <p class="text-gray-600 mt-1">{{ $item->description }}</p>
    </div>
</x-sumimasen-cms-loop-grid>