<?php

namespace Littleboy130491\Sumimasen\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Littleboy130491\Sumimasen\Filament\Pages\ManageGeneralSettings;
use Littleboy130491\Sumimasen\Filament\Resources\CategoryResource;
use Littleboy130491\Sumimasen\Filament\Resources\CommentResource;
use Littleboy130491\Sumimasen\Filament\Resources\ComponentResource;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource;
use Littleboy130491\Sumimasen\Filament\Resources\PostResource;
use Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource;
use Littleboy130491\Sumimasen\Filament\Resources\TagResource;
use Littleboy130491\Sumimasen\Filament\Resources\UserResource;
use Littleboy130491\Sumimasen\Livewire\LikeButton;
use Littleboy130491\Sumimasen\Livewire\SubmissionForm;

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

        Filament::serving(function () {
            Filament::registerResources([
                CategoryResource::class,
                CommentResource::class,
                ComponentResource::class,
                PageResource::class,
                PostResource::class,
                SubmissionResource::class,
                TagResource::class,
                UserResource::class,
            ]);

            Filament::registerPages([
                ManageGeneralSettings::class,
            ]);

            Filament::registerUserMenuItems([
                UserMenuItem::make()
                    ->label('Settings')
                    ->url(ManageGeneralSettings::getUrl())
                    ->icon('heroicon-o-cog'),
            ]);
        });

        Livewire::component('like-button', LikeButton::class);
        Livewire::component('submission-form', SubmissionForm::class);

        Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components', 'cms');
    }
}