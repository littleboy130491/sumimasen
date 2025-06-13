<?php

namespace Littleboy130491\Sumimasen;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Facades\Filament;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Littleboy130491\Sumimasen\Console\Commands\InstallCommand;
use Littleboy130491\Sumimasen\Models\Comment;
use Littleboy130491\Sumimasen\Observers\CommentObserver;
use SolutionForest\FilamentTranslateField\Facades\FilamentTranslateField;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SumimasenServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sumimasen-cms';

    public function register(): void
    {
        parent::register();

        // Add this binding for MigrationCreator
        $this->app->singleton(MigrationCreator::class, function ($app) {
            return new MigrationCreator($app['files'], null);
        });
    }

    public function packageBooted(): void
    {
        $this->bootMultilanguageSupport();
        $this->bootObservers();
        $this->bootDebugMode();
        $this->bootFilamentResources();
        $this->bootLivewireComponents();
        $this->bootBladeComponents();
        $this->bootScheduledTasks();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('cms')
            ->hasViews()
            ->hasMigrations($this->getMigrations())
            ->hasCommands($this->getCommands());
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        // Publish migrations with custom tag
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'cms-migrations');

        // Publish views with custom tag
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/cms'),
        ], 'cms-views');
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            InstallCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\CreateExporterCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\CreateImporterCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\CreateMigrationCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\CreateModelCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\GenerateRolesCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\GenerateSitemap::class,
            \Littleboy130491\Sumimasen\Console\Commands\PublishScheduledContent::class,
            \Littleboy130491\Sumimasen\Console\Commands\RefreshInstagramToken::class,
            \Littleboy130491\Sumimasen\Console\Commands\SyncCuratorMedia::class,
            \Littleboy130491\Sumimasen\Console\Commands\TestLoginNotification::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_posts_table',
            'create_pages_table',
            'create_categories_table',
            'create_tags_table',
            'create_category_post_table',
            'create_post_tag_table',
            'create_submissions_table',
            'create_notifications_table',
            'create_imports_table',
            'create_exports_table',
            'create_failed_import_rows_table',
            'create_comments_table',
            'create_components_table',
            'update_menus_table',
        ];
    }

    private function bootMultilanguageSupport(): void
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
    }

    private function bootObservers(): void
    {
        Comment::observe(CommentObserver::class);
    }

    private function bootDebugMode(): void
    {
        $debugConfig = config('cms.debug_mode');

        // Only boot if debug mode is enabled and environment is allowed
        if (! $debugConfig['enabled'] || ! in_array(app()->environment(), $debugConfig['environments'])) {
            return;
        }

        // View Composer to collect view data
        View::composer('*', function ($view) {
            if (app()->bound('debug.collector')) {
                app('debug.collector')->addViewData(
                    $view->getName(),
                    $view->getData()
                );
            }
        });

        // Database Query Listener
        if ($debugConfig['include_queries']) {
            DB::listen(function ($query) {
                if (app()->bound('debug.collector')) {
                    app('debug.collector')->addQueryData([
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // Cache Hit/Miss Tracking
        if ($debugConfig['include_cache_info']) {
            Event::listen(CacheHit::class, function (CacheHit $event) {
                if (app()->bound('debug.collector')) {
                    app('debug.collector')->addCacheData(true, $event->key);
                }
            });

            Event::listen(CacheMissed::class, function (CacheMissed $event) {
                if (app()->bound('debug.collector')) {
                    app('debug.collector')->addCacheData(false, $event->key);
                }
            });
        }
    }

    private function bootFilamentResources(): void
    {
        // Automatically register the plugin with all Filament panels
        if (class_exists('Filament\Facades\Filament')) {
            // Register plugin with all panels automatically
            $this->app->resolving('filament', function () {
                if (method_exists(\Filament\Facades\Filament::class, 'getCurrentPanel')) {
                    $panels = \Filament\Facades\Filament::getPanels();

                    foreach ($panels as $panel) {
                        if (! $panel->hasPlugin('sumimasen-cms')) {
                            $panel->plugin(SumimasenPlugin::make());
                        }
                    }
                }
            });

            Filament::serving(function () {
                // Fallback: Register resources directly if plugin system fails
                if (! app()->bound('sumimasen-plugin-registered')) {
                    Filament::registerResources([
                        \Littleboy130491\Sumimasen\Filament\Resources\CategoryResource::class,
                        \Littleboy130491\Sumimasen\Filament\Resources\CommentResource::class,
                        \Littleboy130491\Sumimasen\Filament\Resources\ComponentResource::class,
                        \Littleboy130491\Sumimasen\Filament\Resources\PageResource::class,
                        \Littleboy130491\Sumimasen\Filament\Resources\PostResource::class,
                        \Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource::class,
                        \Littleboy130491\Sumimasen\Filament\Resources\TagResource::class,
                        \Littleboy130491\Sumimasen\Filament\Resources\UserResource::class,
                    ]);

                    Filament::registerPages([
                        \Littleboy130491\Sumimasen\Filament\Pages\ManageGeneralSettings::class,
                    ]);

                    app()->instance('sumimasen-plugin-registered', true);
                }

            });
        }
    }

    private function bootLivewireComponents(): void
    {
        // Only register Livewire components if Livewire is available
        if (class_exists('Livewire\Livewire')) {
            \Livewire\Livewire::component('like-button', \Littleboy130491\Sumimasen\Livewire\LikeButton::class);
            \Livewire\Livewire::component('submission-form', \Littleboy130491\Sumimasen\Livewire\SubmissionForm::class);
        }
    }

    private function bootBladeComponents(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/resources/views/components', 'cms');
    }

    private function bootScheduledTasks(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

                // Publish scheduled content every 30 minutes
                $schedule->command('cms:publish-scheduled-content')
                    ->everyThirtyMinutes()
                    ->withoutOverlapping();

                // Refresh Instagram token monthly
                $schedule->command('cms:refresh-instagram-token')
                    ->monthly()
                    ->withoutOverlapping();
            });
        }
    }
}
