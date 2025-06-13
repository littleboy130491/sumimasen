<div class="mb-4">
    <form>
        <label for="ig-type" class="mr-2 font-semibold">Filter:</label>
        <select id="ig-type" name="type" onchange="this.form.submit()" class="border rounded p-1">
            <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All</option>
            <option value="image" {{ request('type') == 'image' ? 'selected' : '' }}>Posts (Image)</option>
            <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>Videos</option>
            <option value="reel" {{ request('type') == 'reel' ? 'selected' : '' }}>Reels</option>
        </select>
    </form>
</div>

@php
    // Set Tailwind grid cols class dynamically, up to 6 columns
    $maxColumns = 6;
    $cols = min(max($columns, 1), $maxColumns);
    $gridClass = 'grid-cols-1';
    if ($cols == 2) {
        $gridClass = 'grid-cols-1 md:grid-cols-2';
    }
    if ($cols == 3) {
        $gridClass = 'grid-cols-1 md:grid-cols-3';
    }
    if ($cols == 4) {
        $gridClass = 'grid-cols-1 md:grid-cols-4';
    }
    if ($cols == 5) {
        $gridClass = 'grid-cols-1 md:grid-cols-5';
    }
    if ($cols == 6) {
        $gridClass = 'grid-cols-1 md:grid-cols-6';
    }
@endphp

<div class="grid {{ $gridClass }} gap-4">
    @forelse($feeds as $feed)
        <div class="rounded shadow p-2 bg-white">
            <a href="{{ $feed['permalink'] }}" target="_blank">
                @if ($feed['media_type'] === 'IMAGE' || $feed['media_type'] === 'CAROUSEL_ALBUM')
                    <img src="{{ $feed['media_url'] }}" class="w-full h-60 object-cover rounded" loading="lazy" />
                @elseif($feed['media_type'] === 'VIDEO')
                    <img src="{{ $feed['thumbnail_url'] ?? $feed['media_url'] }}"
                        class="w-full h-60 object-cover rounded" loading="lazy" />
                @endif
            </a>
        </div>
    @empty
        <p>No feeds found for this filter.</p>
    @endforelse
</div>
