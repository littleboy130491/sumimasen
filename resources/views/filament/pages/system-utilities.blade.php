<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Cache Management -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-fire class="h-8 w-8 text-red-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">Clear All Cache</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Clear all cache types including application, configuration, view, route, response, and CMS-specific
                caches.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Application & Config Cache</li>
                <li>• View & Route Cache</li>
                <li>• Response Cache</li>
                <li>• CMS-specific Cache</li>
            </ul>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>Note:</strong> Clearing cache may temporarily slow down your application.
                </p>
            </div>
            <div class="mt-4">
                <button
                    wire:confirm="This will clear all cache types (application, config, view, route, response, and CMS caches). Are you sure you want to continue?"
                    wire:click="clearAllCacheAction" wire:loading.attr="disabled" wire:target="clearAllCacheAction"
                    @disabled($clearingCache)
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400">
                    <div wire:loading.remove wire:target="clearAllCacheAction">
                        <x-heroicon-o-fire class="w-4 h-4 mr-2" />
                    </div>
                    <div wire:loading wire:target="clearAllCacheAction">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                    <span wire:loading.remove wire:target="clearAllCacheAction">Clear All Cache</span>
                    <span wire:loading wire:target="clearAllCacheAction">Clearing...</span>
                </button>
            </div>
        </div>

        <!-- System Optimization -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-bolt class="h-8 w-8 text-green-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">Optimize Application</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Optimize your application by caching configurations, routes, and views for better performance.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Config Caching</li>
                <li>• Route Caching</li>
                <li>• View Caching</li>
                <li>• Autoloader Optimization</li>
            </ul>
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
                <p class="text-xs text-green-800">
                    <strong>Recommended:</strong> Run after clearing cache.
                </p>
            </div>
            <div class="mt-4">
                <button wire:click="optimizeApplicationAction" wire:loading.attr="disabled"
                    wire:target="optimizeApplicationAction" @disabled($optimizing)
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400">
                    <div wire:loading.remove wire:target="optimizeApplicationAction">
                        <x-heroicon-o-bolt class="w-4 h-4 mr-2" />
                    </div>
                    <div wire:loading wire:target="optimizeApplicationAction">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                    <span wire:loading.remove wire:target="optimizeApplicationAction">Optimize Application</span>
                    <span wire:loading wire:target="optimizeApplicationAction">Optimizing...</span>
                </button>
            </div>
        </div>

        <!-- Sitemap Generation -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-map class="h-8 w-8 text-blue-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">Generate Sitemap</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Generate XML sitemap for all published content across all languages.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Multi-language Support</li>
                <li>• All Published Content</li>
                <li>• SEO Optimization</li>
                <li>• Auto URL Discovery</li>
            </ul>
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                <p class="text-xs text-blue-800">
                    <strong>Output:</strong> Creates sitemap.xml in public folder.
                </p>
            </div>
            <div class="mt-4">
                <button wire:click="generateSitemapAction" wire:loading.attr="disabled"
                    wire:target="generateSitemapAction" @disabled($generatingSitemap)
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                    <div wire:loading.remove wire:target="generateSitemapAction">
                        <x-heroicon-o-map class="w-4 h-4 mr-2" />
                    </div>
                    <div wire:loading wire:target="generateSitemapAction">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                    <span wire:loading.remove wire:target="generateSitemapAction">Generate Sitemap</span>
                    <span wire:loading wire:target="generateSitemapAction">Generating...</span>
                </button>
            </div>
        </div>

        <!-- Role Management -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-shield-check class="h-8 w-8 text-yellow-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">Generate Roles</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Create or update CMS roles with appropriate permissions.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Super Admin (All permissions)</li>
                <li>• Admin (Most permissions)</li>
                <li>• Editor (Limited permissions)</li>
            </ul>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>Note:</strong> Will update existing roles if they exist.
                </p>
            </div>
            <div class="mt-4">
                <button
                    wire:confirm="This will create/update super admin, admin, and editor roles with appropriate permissions. Existing roles will be updated."
                    wire:click="generateRolesAction" wire:loading.attr="disabled" wire:target="generateRolesAction"
                    @disabled($generatingRoles)
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400">
                    <div wire:loading.remove wire:target="generateRolesAction">
                        <x-heroicon-o-shield-check class="w-4 h-4 mr-2" />
                    </div>
                    <div wire:loading wire:target="generateRolesAction">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                    <span wire:loading.remove wire:target="generateRolesAction">Generate Roles</span>
                    <span wire:loading wire:target="generateRolesAction">Generating...</span>
                </button>
            </div>
        </div>

        <!-- Instagram Token -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-photo class="h-8 w-8 text-gray-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">Instagram Token</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Refresh Instagram long-lived access token to maintain API connectivity.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Token Refresh</li>
                <li>• Auto .env Update</li>
                <li>• API Connectivity</li>
                <li>• Long-lived Token</li>
            </ul>
            <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded">
                <p class="text-xs text-gray-800">
                    <strong>Requirement:</strong> Valid Instagram API configuration.
                </p>
            </div>
            <div class="mt-4">
                <button wire:click="refreshInstagramTokenAction" wire:loading.attr="disabled"
                    wire:target="refreshInstagramTokenAction" @disabled($refreshingToken)
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400">
                    <div wire:loading.remove wire:target="refreshInstagramTokenAction">
                        <x-heroicon-o-photo class="w-4 h-4 mr-2" />
                    </div>
                    <div wire:loading wire:target="refreshInstagramTokenAction">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                    <span wire:loading.remove wire:target="refreshInstagramTokenAction">Refresh Token</span>
                    <span wire:loading wire:target="refreshInstagramTokenAction">Refreshing...</span>
                </button>
            </div>
        </div>

        <!-- Image Optimization -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-sparkles class="h-8 w-8 text-purple-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">ShortPixel Optimize</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Compress and optimize images using ShortPixel API to reduce file sizes and improve performance.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Lossless Compression</li>
                <li>• WebP/AVIF Support</li>
                <li>• Batch Processing</li>
                <li>• Backup Creation</li>
            </ul>
            <div class="mt-4 p-3 bg-purple-50 border border-purple-200 rounded">
                <p class="text-xs text-purple-800">
                    <strong>Requirement:</strong> ShortPixel API key configured.
                </p>
            </div>
            <div class="mt-4">
                <button
                    wire:confirm="This will optimize images in the media folder using ShortPixel API. Make sure you have configured your API key."
                    wire:click="shortpixelOptimizeAction" wire:loading.attr="disabled"
                    wire:target="shortpixelOptimizeAction" @disabled($optimizingImages)
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400">
                    <div wire:loading.remove wire:target="shortpixelOptimizeAction">
                        <x-heroicon-o-sparkles class="w-4 h-4 mr-2" />
                    </div>
                    <div wire:loading wire:target="shortpixelOptimizeAction">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                    <span wire:loading.remove wire:target="shortpixelOptimizeAction">Optimize Images</span>
                    <span wire:loading wire:target="shortpixelOptimizeAction">Optimizing...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
            <div>
                <h4 class="text-sm font-medium text-blue-800 mb-1">Usage Instructions</h4>
                <p class="text-sm text-blue-700">
                    Use the buttons on each utility card to execute system commands. Each operation will show a
                    notification with the result.
                    For production environments, it's recommended to clear cache during low-traffic periods.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>