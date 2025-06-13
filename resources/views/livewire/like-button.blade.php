<div>
    <button type="button" wire:click="toggleLike"
        class="inline-flex items-center gap-2 rounded-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 {{ $this->getSizeClasses() }} {{ $this->getVariantClasses() }} {{ $hasLiked ? 'text-red-600' : '' }}"
        wire:loading.attr="disabled" wire:target="toggleLike">
        <!-- Heart Icon -->
        <svg class="w-5 h-5 transition-all duration-200 {{ $hasLiked ? 'text-red-600' : '' }}"
            fill="{{ $hasLiked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"
            wire:loading.class="animate-pulse" wire:target="toggleLike">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>

        @if ($showCount)
            <span class="font-semibold">{{ number_format($likesCount) }}</span>
        @endif

        <span wire:loading.remove wire:target="toggleLike">
            {{ $hasLiked ? 'Liked' : 'Like' }}
        </span>

        <span wire:loading wire:target="toggleLike" class="text-sm">
            ...
        </span>
    </button>
</div>
