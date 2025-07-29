<?php

namespace Littleboy130491\Sumimasen\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class SystemUtilities extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string $view = 'sumimasen-cms::filament.pages.system-utilities';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'System Utilities';

    protected static ?string $navigationLabel = 'Utilities';

    protected static ?int $navigationSort = 99;

    public function getHeaderActions(): array
    {
        return [
            Action::make('clear_all_cache')
                ->label('Clear All Cache')
                ->icon('heroicon-o-fire')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clear All Cache')
                ->modalDescription('This will clear all cache types (application, config, view, route, response, and CMS caches). Are you sure you want to continue?')
                ->action(function () {
                    try {
                        Artisan::call('cms:clear-all-caches');
                        Notification::make()
                            ->title('All caches cleared successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to clear all caches')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('optimize_application')
                ->label('Optimize Application')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->action(function () {
                    try {
                        Artisan::call('optimize');
                        Notification::make()
                            ->title('Application optimized successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to optimize application')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('generate_sitemap')
                ->label('Generate Sitemap')
                ->icon('heroicon-o-map')
                ->color('info')
                ->action(function () {
                    try {
                        Artisan::call('sitemap:generate');
                        Notification::make()
                            ->title('Sitemap generated successfully')
                            ->body('Sitemap has been created at public/sitemap.xml')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to generate sitemap')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('generate_roles')
                ->label('Generate Roles')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generate CMS Roles')
                ->modalDescription('This will create/update super admin, admin, and editor roles with appropriate permissions. Existing roles will be updated.')
                ->action(function () {
                    try {
                        Artisan::call('cms:generate-roles', ['--force' => true]);
                        Notification::make()
                            ->title('Roles generated successfully')
                            ->body('Super admin, admin, and editor roles have been created/updated')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to generate roles')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('refresh_instagram_token')
                ->label('Refresh Instagram Token')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->action(function () {
                    try {
                        Artisan::call('instagram:refresh-token');
                        Notification::make()
                            ->title('Instagram token refreshed successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to refresh Instagram token')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('shortpixel_optimize')
                ->label('Optimize Images (ShortPixel)')
                ->icon('heroicon-o-sparkles')
                ->color('purple')
                ->requiresConfirmation()
                ->modalHeading('Optimize Images with ShortPixel')
                ->modalDescription('This will optimize images in the media folder using ShortPixel API. Make sure you have configured your API key.')
                ->action(function () {
                    try {
                        Artisan::call('cms:shortpixel-optimize');
                        Notification::make()
                            ->title('Image optimization completed')
                            ->body('Check the console output for detailed results')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to optimize images')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
