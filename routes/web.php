<?php

use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Littleboy130491\Sumimasen\Http\Controllers\HomeController;
use Littleboy130491\Sumimasen\Http\Controllers\PreviewEmailController;
use Littleboy130491\Sumimasen\Http\Controllers\SingleContentController;
use Littleboy130491\Sumimasen\Http\Controllers\StaticPageController;
use Littleboy130491\Sumimasen\Http\Controllers\TaxonomyController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Routes for previewing emails and components
Route::prefix('/{lang}/preview')
    ->whereIn('lang', array_keys(Config::get('cms.language_available', ['en' => 'English'])))
    ->middleware([
        'setLocale',
        'web',
        'doNotCacheResponse',
        Authenticate::class,
    ])
    ->group(function () {
        // List all available email templates
        Route::get('/email', [PreviewEmailController::class, 'emailInfo'])
            ->name('preview.email.list');
        // Preview a specific email template by slug
        Route::get('/email/{slug}', [PreviewEmailController::class, 'emailTemplate'])
            ->name('preview.email.detail');
        // Preview a dynamic component
        Route::get('/component/{slug}', function ($lang, $slug) {
            return view('sumimasen-cms::preview-component', compact('slug'));
        });
        // Preview a submission form
        Route::get('/submission-form', function () {
            return view('sumimasen-cms::submission-form-test');
        });
    });

// Redirect root to default language
Route::get('/', function () {
    $defaultLang = Config::get('cms.default_language', 'en');

    return redirect()->to($defaultLang);
})->middleware(['setLocale', 'web']);

Route::fallback(function (Request $request) {
    // If a route got here, nothing else matched.
    $availableLanguages = array_keys(Config::get('cms.language_available', ['en' => 'English']));

    // Do not touch requests that already start with a known language
    $path = trim($request->path(), '/');            // e.g. "blog/post"
    $firstSegment = $path === '' ? '' : Str::before($path, '/');
    if (in_array($firstSegment, $availableLanguages, true)) {
        abort(404); // keep standard 404 for unknown lang paths
    }

    // Respect known “reserved” prefixes (admin, filament, up, etc.)
    $reserved = array_merge(
        ['admin', 'filament', 'filament-impersonate', 'up'],
        $availableLanguages
    );
    if ($firstSegment !== '' && in_array($firstSegment, $reserved, true)) {
        abort(404);
    }

    // Infer current language from referer, else use default
    $currentLang = Config::get('cms.default_language', 'en');
    if ($ref = $request->headers->get('referer')) {
        $refPath = parse_url($ref, PHP_URL_PATH) ?: '';
        $refSeg0 = trim($refPath, '/') === '' ? '' : Str::before(trim($refPath, '/'), '/');
        if (in_array($refSeg0, $availableLanguages, true)) {
            $currentLang = $refSeg0;
        }
    }

    $qs = $request->getQueryString();
    $redirectTo = '/' . $currentLang . ($path ? "/$path" : '');
    if ($qs) {
        $redirectTo .= "?$qs";
    }

    return redirect()->to($redirectTo);
})->middleware(['setLocale', 'web']);

Route::prefix('{lang}')
    ->whereIn('lang', array_keys(Config::get('cms.language_available', ['en' => 'English'])))
    ->middleware(['setLocale', 'web'])
    ->group(function () {

        // Cache the slug arrays to avoid looping on every request, for 1 day = 86400 sec
        $slugArrays = cache()->remember('cms.route_slugs', 86400, function () {
            $allModelConfigs = Config::get('cms.content_models', []);

            $contentArchiveKeys = [];
            $contentSingleKeys = [];
            $taxonomyArchiveKeys = [];

            foreach ($allModelConfigs as $key => $details) {
                // Skip the '' key
                if (empty($key)) {
                    continue;
                }

                $type = $details['type'] ?? null;
                $hasArchive = $details['has_archive'] ?? false;
                $hasSingle = $details['has_single'] ?? false;

                // Use custom slug if provided, otherwise fallback to key
                $slug = $details['slug'] ?? $key;

                if ($type === 'content') {
                    if ($hasArchive) {
                        $contentArchiveKeys[] = $slug;
                    }
                    if ($hasSingle) {
                        $contentSingleKeys[] = $slug;
                    }
                } elseif ($type === 'taxonomy') {
                    if ($hasArchive) {
                        $taxonomyArchiveKeys[] = $slug;
                    }
                }
            }

            return [
                'content_archive' => $contentArchiveKeys,
                'content_single' => $contentSingleKeys,
                'taxonomy_archive' => $taxonomyArchiveKeys,
            ];
        });

        $contentArchiveKeys = $slugArrays['content_archive'];
        $contentSingleKeys = $slugArrays['content_single'];
        $taxonomyArchiveKeys = $slugArrays['taxonomy_archive'];

        // Regex for matching valid keys from your config.
        // preg_quote is important for special characters in keys.
        $contentArchiveKeysRegex = !empty($contentArchiveKeys) ? implode('|', array_map('preg_quote', $contentArchiveKeys)) : '^\b$'; // Matches nothing if empty
        $contentSingleKeysRegex = !empty($contentSingleKeys) ? implode('|', array_map('preg_quote', $contentSingleKeys)) : '^\b$'; // Matches nothing if empty
        $taxonomyArchiveKeysRegex = !empty($taxonomyArchiveKeys) ? implode('|', array_map('preg_quote', $taxonomyArchiveKeys)) : '^\b$'; // Matches nothing if empty
    
        // General slug regex - matches the form validation regex
        $slugRegex = '[a-zA-Z0-9-_]+';

        Route::get('/', HomeController::class)->name('cms.home');

        // Content single route.
        // Redirect to static page if content_type_key matches static_page_slug.
        Route::get('/{content_type_key}/{content_slug}', SingleContentController::class)
            ->where('content_type_key', $contentSingleKeysRegex)
            ->where('content_slug', $slugRegex)
            ->name('cms.single.content');

        // Taxonomy archive route, ex: categories/news, tags/latest
        Route::get('/{taxonomy_key}/{taxonomy_slug}', TaxonomyController::class)
            ->where('taxonomy_key', $taxonomyArchiveKeysRegex)
            ->where('taxonomy_slug', $slugRegex)
            ->name('cms.taxonomy.archive');

        // Static page route.
        Route::get('/{slug}', StaticPageController::class)
            ->where('slug', $slugRegex)
            ->name('cms.page');

    });
