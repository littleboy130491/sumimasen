<?php

namespace Littleboy130491\Sumimasen\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class SystemUtilities extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string $view = 'sumimasen-cms::filament.pages.system-utilities';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'System Utilities';

    protected static ?string $navigationLabel = 'Utilities';

    protected static ?int $navigationSort = 99;

    public bool $clearingCache = false;

    public bool $optimizing = false;

    public bool $generatingSitemap = false;

    public bool $generatingRoles = false;

    public bool $refreshingToken = false;

    public bool $optimizingImages = false;

    public function clearAllCacheAction()
    {
        if ($this->clearingCache) {
            return;
        }

        $this->clearingCache = true;

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
        } finally {
            $this->clearingCache = false;
        }
    }

    public function optimizeApplicationAction()
    {
        if ($this->optimizing) {
            return;
        }

        $this->optimizing = true;

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
        } finally {
            $this->optimizing = false;
        }
    }

    public function generateSitemapAction()
    {
        if ($this->generatingSitemap) {
            return;
        }

        $this->generatingSitemap = true;

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
        } finally {
            $this->generatingSitemap = false;
        }
    }

    public function generateRolesAction()
    {
        if ($this->generatingRoles) {
            return;
        }

        $this->generatingRoles = true;

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
        } finally {
            $this->generatingRoles = false;
        }
    }

    public function refreshInstagramTokenAction()
    {
        if ($this->refreshingToken) {
            return;
        }

        $this->refreshingToken = true;

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
        } finally {
            $this->refreshingToken = false;
        }
    }

    public function shortpixelOptimizeAction()
    {
        if ($this->optimizingImages) {
            return;
        }

        $this->optimizingImages = true;

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
        } finally {
            $this->optimizingImages = false;
        }
    }
}
