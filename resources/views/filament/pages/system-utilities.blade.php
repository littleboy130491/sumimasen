<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Cache Management -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-fire class="h-8 w-8 text-red-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">Clear All Cache</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Clear all cache types including application, configuration, view, route, response, and CMS-specific caches.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Application & Config Cache</li>
                <li>• View & Route Cache</li>
                <li>• Response Cache</li>
                <li>• CMS-specific Cache</li>
            </ul>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>Warning:</strong> May temporarily slow down your application.
                </p>
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
        </div>

        <!-- Role Management -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-shield-check class="h-8 w-8 text-yellow-500 mr-3" />
                <h3 class="text-lg font-medium text-gray-900">Generate Roles</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Create or update CMS roles with appropriate permissions using Filament Shield.
            </p>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Super Admin (All permissions)</li>
                <li>• Admin (Most permissions)</li>
                <li>• Editor (Limited permissions)</li>
                <li>• Shield Integration</li>
            </ul>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>Note:</strong> Will update existing roles if they exist.
                </p>
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
        </div>
    </div>

    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
            <div>
                <h4 class="text-sm font-medium text-blue-800 mb-1">Usage Instructions</h4>
                <p class="text-sm text-blue-700">
                    Use the action buttons in the header to execute system utilities. Each action will show a notification with the result. 
                    For production environments, it's recommended to clear cache during low-traffic periods.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>