<?php

namespace Littleboy130491\Sumimasen\Providers;

use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CmsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('cms')
            ->hasConfigFile('cms')
            ->hasViews()
            ->hasMigrations([
                'create_users_table',
                'create_cache_table',
                'create_jobs_table',
                'create_settings_table',
                'create_media_table',
                'add_tenant_aware_column_to_media_table',
                'create_breezy_sessions_table',
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
                'create_permission_tables',
                'create_seo_suite_table',
                'create_menus_table',
                'create_comments_table',
                'create_components_table',
                'update_menus_table',
            ])
            ->hasCommands([
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
            ]);
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Only register Filament resources if Filament is available
        if (class_exists('Filament\Facades\Filament')) {
            \Filament\Facades\Filament::serving(function () {
                \Filament\Facades\Filament::registerResources([
                    \Littleboy130491\Sumimasen\Filament\Resources\CategoryResource::class,
                    \Littleboy130491\Sumimasen\Filament\Resources\CommentResource::class,
                    \Littleboy130491\Sumimasen\Filament\Resources\ComponentResource::class,
                    \Littleboy130491\Sumimasen\Filament\Resources\PageResource::class,
                    \Littleboy130491\Sumimasen\Filament\Resources\PostResource::class,
                    \Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource::class,
                    \Littleboy130491\Sumimasen\Filament\Resources\TagResource::class,
                    \Littleboy130491\Sumimasen\Filament\Resources\UserResource::class,
                ]);

                \Filament\Facades\Filament::registerPages([
                    \Littleboy130491\Sumimasen\Filament\Pages\ManageGeneralSettings::class,
                ]);

                if (class_exists('Filament\Navigation\UserMenuItem')) {
                    \Filament\Facades\Filament::registerUserMenuItems([
                        \Filament\Navigation\UserMenuItem::make()
                            ->label('Settings')
                            ->url(\Littleboy130491\Sumimasen\Filament\Pages\ManageGeneralSettings::getUrl())
                            ->icon('heroicon-o-cog'),
                    ]);
                }
            });
        }

        // Only register Livewire components if Livewire is available
        if (class_exists('Livewire\Livewire')) {
            \Livewire\Livewire::component('like-button', \Littleboy130491\Sumimasen\Livewire\LikeButton::class);
            \Livewire\Livewire::component('submission-form', \Littleboy130491\Sumimasen\Livewire\SubmissionForm::class);
        }

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'cms');
    }
}
