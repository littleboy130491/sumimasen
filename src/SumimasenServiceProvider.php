<?php

namespace Littleboy130491\Sumimasen;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Auth\Events\Login;
use Littleboy130491\Sumimasen\Listeners\SendAdminLoginNotification;
use Littleboy130491\Sumimasen\Models\Comment;
use Littleboy130491\Sumimasen\Observers\ActivityLogObserver;
use Littleboy130491\Sumimasen\Observers\CommentObserver;
use Littleboy130491\Sumimasen\Services\ActivityLogger;
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

        // Register ActivityLogger service
        $this->app->singleton(ActivityLogger::class);
    }

    public function packageBooted(): void
    {
        $this->bootMultilanguageSupport();
        $this->bootObservers();
        $this->bootEventListeners();
        $this->bootDebugMode();
        $this->bootBladeComponents();
        $this->bootScheduledTasks();
        $this->bootPolicies();
        $this->bootActivityLogging();

        $this->app->booted(function () {
            // Boot Livewire components after everything else
            $this->bootLivewireComponents();
            $router = $this->app['router'];
            $router->aliasMiddleware('setLocale', \Littleboy130491\Sumimasen\Http\Middleware\SetLocale::class);
            $router->aliasMiddleware('doNotCacheResponse', \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class);
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile(['cms', 'shortpixel'])
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations($this->getMigrations())
            ->hasCommands($this->getCommands())
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            \Littleboy130491\Sumimasen\Console\Commands\InstallCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\CmsFinalizeCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\CreateMigrationCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\CreateModelCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\GenerateRolesCommand::class,
            \Littleboy130491\Sumimasen\Console\Commands\GenerateSitemap::class,
            \Littleboy130491\Sumimasen\Console\Commands\PublishScheduledContent::class,
            \Littleboy130491\Sumimasen\Console\Commands\RefreshInstagramToken::class,
            \Littleboy130491\Sumimasen\Console\Commands\SyncCuratorMedia::class,
            \Littleboy130491\Sumimasen\Console\Commands\TestLoginNotification::class,
            \Littleboy130491\Sumimasen\Console\Commands\ClearCmsRoutes::class,
            \Littleboy130491\Sumimasen\Console\Commands\ClearAllCaches::class,
            \Littleboy130491\Sumimasen\Console\Commands\ShortPixelOptimizeCommand::class,
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
            'create_archives_table',
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
            'create_general_settings',
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
        
        // Register activity logging observer for all CMS models
        $models = [
            \Littleboy130491\Sumimasen\Models\Archive::class,
            \Littleboy130491\Sumimasen\Models\Category::class,
            \Littleboy130491\Sumimasen\Models\Comment::class,
            \Littleboy130491\Sumimasen\Models\Component::class,
            \Littleboy130491\Sumimasen\Models\Page::class,
            \Littleboy130491\Sumimasen\Models\Post::class,
            \Littleboy130491\Sumimasen\Models\Submission::class,
            \Littleboy130491\Sumimasen\Models\Tag::class,
        ];

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::observe(ActivityLogObserver::class);
            }
        }
    }

    private function bootEventListeners(): void
    {
        Event::listen(Login::class, SendAdminLoginNotification::class);
    }

    private function bootDebugMode(): void
    {
        $debugConfig = config('cms.debug_mode');

        // Only boot if debug mode is enabled and environment is allowed
        if (!$debugConfig['enabled'] || !in_array(app()->environment(), $debugConfig['environments'])) {
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

    private function bootLivewireComponents(): void
    {
        if (!class_exists(\Livewire\Livewire::class)) {
            return;
        }

        // Auto-register all Livewire components under this namespace
        $baseNamespace = 'Littleboy130491\\Sumimasen\\Livewire';
        $basePath = __DIR__ . '/Livewire';

        if (!is_dir($basePath)) {
            return;
        }

        foreach (scandir($basePath) as $file) {
            if (in_array($file, ['.', '..']) || !str_ends_with($file, '.php')) {
                continue;
            }

            $class = $baseNamespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($class)) {
                // Add package prefix to avoid conflicts
                $alias = static::$name . '.' . \Illuminate\Support\Str::kebab(class_basename($class));
                \Livewire\Livewire::component($alias, $class);
            }
        }
    }

    private function bootBladeComponents(): void
    {
        Blade::anonymousComponentPath(__DIR__ . '/resources/views/components', static::$name);
        // Register class-based components in this namespace
        Blade::componentNamespace('Littleboy130491\\Sumimasen\\View\\Components', static::$name);
    }

    private function bootScheduledTasks(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

                // Publish scheduled content every 30 minutes
                $schedule->command('cms:publish-scheduled')
                    ->everyThirtyMinutes()
                    ->withoutOverlapping();

                // Refresh Instagram token monthly
                $schedule->command('cms:refresh-instagram-token')
                    ->monthly()
                    ->withoutOverlapping();

                // Clear all caches daily
                $schedule->command('cms:clear-all-caches')
                    ->daily()
                    ->withoutOverlapping();

                // Generate sitemap daily
                $schedule->command('cms:generate-sitemap')
                    ->daily()
                    ->withoutOverlapping();
            });
        }
    }

    private function bootPolicies(): void
    {
        if (class_exists(\Awcodes\Curator\Models\Media::class) && class_exists(\App\Policies\MediaPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Awcodes\Curator\Models\Media::class,
                \App\Policies\MediaPolicy::class
            );
        }

        if (class_exists(\Datlechin\FilamentMenuBuilder\Models\Menu::class) && class_exists(\App\Policies\MenuPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Datlechin\FilamentMenuBuilder\Models\Menu::class,
                \App\Policies\MenuPolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Category::class) && class_exists(\App\Policies\CategoryPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Category::class,
                \App\Policies\CategoryPolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Comment::class) && class_exists(\App\Policies\CommentPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Comment::class,
                \App\Policies\CommentPolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Component::class) && class_exists(\App\Policies\ComponentPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Component::class,
                \App\Policies\ComponentPolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Page::class) && class_exists(\App\Policies\PagePolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Page::class,
                \App\Policies\PagePolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Post::class) && class_exists(\App\Policies\PostPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Post::class,
                \App\Policies\PostPolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Submission::class) && class_exists(\App\Policies\SubmissionPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Submission::class,
                \App\Policies\SubmissionPolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Tag::class) && class_exists(\App\Policies\TagPolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Tag::class,
                \App\Policies\TagPolicy::class
            );
        }

        if (class_exists(\Littleboy130491\Sumimasen\Models\Archive::class) && class_exists(\App\Policies\ArchivePolicy::class)) {
            \Illuminate\Support\Facades\Gate::policy(
                \Littleboy130491\Sumimasen\Models\Archive::class,
                \App\Policies\ArchivePolicy::class
            );
        }

    }

    private function bootActivityLogging(): void
    {
        // Configure custom log channel for activity logs
        config(['logging.channels.activity' => [
            'driver' => 'single',
            'path' => storage_path('logs/activity.log'),
            'level' => 'info',
            'days' => 30,
        ]]);
    }
}
