<?php

namespace Littleboy130491\Sumimasen\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\MigrationCreator;
use SolutionForest\FilamentTranslateField\Facades\FilamentTranslateField;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Littleboy130491\Sumimasen\Models\Comment;
use Littleboy130491\Sumimasen\Observers\CommentObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Add this binding for MigrationCreator
        $this->app->singleton(MigrationCreator::class, function ($app) {
            // Manually resolve the Filesystem dependency and pass null for the custom stub path
            return new MigrationCreator($app['files'], null);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('cms.multilanguage_enabled') && config('cms.language_available')) {
            $languages = config('cms.language_available');
            $default = config('cms.default_language');

            // Reorder languages so default is first
            if (isset($languages[$default])) {
                $defaultLang = [$default => $languages[$default]];
                unset($languages[$default]);
                $languages = $defaultLang + $languages;
            }

            $localeKeys = array_keys($languages);

            FilamentTranslateField::defaultLocales($localeKeys);
            LanguageSwitch::configureUsing(function (LanguageSwitch $switch) use ($localeKeys) {
                $switch->locales($localeKeys);
            });
        }

        Comment::observe(CommentObserver::class);
    }
}
