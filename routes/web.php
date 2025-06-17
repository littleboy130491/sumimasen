<?php

use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Littleboy130491\Sumimasen\Http\Controllers\ContentController;
use Littleboy130491\Sumimasen\Http\Controllers\PreviewEmailController;

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
        Route::get('/component/{slug}', function ($slug) {
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
});

// Redirect routes without language prefix to default language
Route::get('/{path}', function (Illuminate\Http\Request $request, $path) {
    $defaultLang = Config::get('cms.default_language', 'en');
    $queryString = $request->getQueryString();
    $redirectUrl = "{$defaultLang}/{$path}";
    if ($queryString) {
        $redirectUrl .= "?{$queryString}";
    }

    return redirect()->to($redirectUrl);
})->where('path', '^(?!'.implode('|', array_keys(Config::get('cms.language_available', ['en' => 'English']))).'/).*');

Route::prefix('{lang}')
    ->whereIn('lang', array_keys(Config::get('cms.language_available', ['en' => 'English'])))
    ->middleware(['setLocale'])
    ->group(function () {

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

            if ($type === 'content') {
                if ($hasArchive) {
                    $contentArchiveKeys[] = $key;
                }
                if ($hasSingle) {
                    $contentSingleKeys[] = $key;
                }
            } elseif ($type === 'taxonomy') {
                if ($hasArchive) {
                    $taxonomyArchiveKeys[] = $key;
                }
            }
        }

        // Regex for matching valid keys from your config.
        // preg_quote is important for special characters in keys.
        $contentArchiveKeysRegex = ! empty($contentArchiveKeys) ? implode('|', array_map('preg_quote', $contentArchiveKeys)) : '^\b$'; // Matches nothing if empty
        $contentSingleKeysRegex = ! empty($contentSingleKeys) ? implode('|', array_map('preg_quote', $contentSingleKeys)) : '^\b$'; // Matches nothing if empty
        $taxonomyArchiveKeysRegex = ! empty($taxonomyArchiveKeys) ? implode('|', array_map('preg_quote', $taxonomyArchiveKeys)) : '^\b$'; // Matches nothing if empty

        // General slug regex
        $slugRegex = '[a-zA-Z0-9-_]+';

        Route::get('/', [ContentController::class, 'home'])->name('cms.home');

        // Content single route. Two segments; first must be a content type key with single views.
        // Redirect to static page if content_type_key matches static_page_slug.
        Route::get('/{content_type_key}/{content_slug}', [ContentController::class, 'singleContent'])
            ->where('content_type_key', $contentSingleKeysRegex)
            ->where('content_slug', $slugRegex)
            ->name('cms.single.content');

        // Taxonomy archive route. Two segments; first must be a taxonomy key.
        Route::get('/{taxonomy_key}/{taxonomy_slug}', [ContentController::class, 'taxonomyArchive'])
            ->where('taxonomy_key', $taxonomyArchiveKeysRegex)
            ->where('taxonomy_slug', $slugRegex)
            ->name('cms.taxonomy.archive');

        // One segment, must be a content type key with an archive. Comes BEFORE static pages.
        // Controller receives $content_type_archive_key.
        Route::get('/{content_type_archive_key}', [ContentController::class, 'archiveContent'])
            ->where('content_type_archive_key', $contentArchiveKeysRegex)
            ->name('cms.archive.content');

        // Static page route.
        // Most generic (single slug) and MUST be defined LAST.
        Route::get('/{page_slug}', [ContentController::class, 'staticPage'])
            ->where('page_slug', $slugRegex)
            ->name('cms.static.page');
    });
