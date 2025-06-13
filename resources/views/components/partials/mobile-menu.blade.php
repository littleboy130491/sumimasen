@props(['location' => 'header'])

@php
    use Datlechin\FilamentMenuBuilder\Models\Menu;
    
    // Get current language from URL segment (first segment after domain)
    $currentLang = request()->segment(1);
    
    // Validate if it's a valid language from your config
    $availableLanguages = array_keys(config('cms.language_available', []));
    if (!in_array($currentLang, $availableLanguages)) {
        $currentLang = config('cms.default_language', 'en'); // fallback to default
    }
    
    $localizedLocation = $location . '_' . $currentLang;
    
    // Try to get the localized menu first, fallback to base location
    $menu = Menu::location($localizedLocation) ?? Menu::location($location);
@endphp

@if ($menu && $menu->menuItems)
    <ul class="mt-10 space-y-4">
        @foreach ($menu->menuItems as $item)
            @if ($item->children)
                <li x-data="{ openSubMenu: null }"
                    @click="if (!$event.target.closest('a')) { openSubMenu === '{{ $item->slug }}' ? openSubMenu = null : openSubMenu = '{{ $item->slug }}' }"
                    class="cursor-pointer select-none">
                    <div class="flex flex-row justify-between items-start w-full">
                        <a href="{{ $item->url }}"
                            class="block text-[var(--color-heading)] hover:text-[var(--color-blue)]">
                            {{ $item->title }}
                        </a>
                        <div class="ml-2 text-[var(--color-heading)] hover:text-[var(--color-blue)]">
                            <svg class="w-4 h-4 transform"
                                :class="{ 'rotate-180': openSubMenu === '{{ $item->slug }}' }"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 011.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <ul x-show="openSubMenu === '{{ $item->slug }}'"
                        class="ml-4 mt-2 space-y-2 text-sm text-[var(--color-text)]" x-cloak>
                        @foreach ($item->children as $child)
                            <li><a href="{{ $child->url }}"
                                    class="block hover:text-[var(--color-blue)]">{{ $child->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li><a href="{{ $item->url }}"
                        class="block text-[var(--color-heading)] hover:text-[var(--color-blue)]">{{ $item->title }}</a>
                </li>
            @endif
        @endforeach
    </ul>
@endif