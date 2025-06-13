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
    <!--Main Menu-->
    <nav class="hidden sm:ml-6 sm:flex">
        <ul class="flex gap-5 items-center">
            @foreach ($menu->menuItems as $item)
                @if ($item->children)
                    <li class="relative group">
                        <a href="{{ $item->url }}"
                            class="inline-flex items-center px-1 pt-1 font-medium text-[var(--color-heading)] hover:text-[var(--color-blue)] focus:outline-none">
                            {{ $item->title }}
                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 011.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>
                        <ul
                            class="absolute left-0 mt-2 w-48 bg-white shadow-lg rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                            @foreach ($item->children as $child)
                                <li>
                                    <a href="{{ $child->url }}"
                                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100">{{ $child->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li>
                        <a href="{{ $item->url }}"
                            class="inline-flex items-center px-1 pt-1 font-medium text-[var(--color-heading)] hover:text-[var(--color-blue)]">
                            {{ $item->title }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>
@endif