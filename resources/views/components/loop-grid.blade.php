@php
    $results = $getResults();
@endphp

<div class="{{ $getCombinedClasses() }}" {!! $getAttributesString() !!}>
    @forelse ($results as $item)
        {{ $slot }}
    @empty
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">No items found.</p>
        </div>
    @endforelse
</div>

@if ($pagination && method_exists($results, 'links'))
    <div class="mt-8">
        {{ $results->links() }}
    </div>
@endif