<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install CMS components and resources';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Publishing CMS configuration...');
        $this->call('vendor:publish', ['--tag' => 'cms-config']);

        $this->info('Publishing CMS migrations...');
        $this->call('vendor:publish', ['--tag' => 'cms-migrations']);

        $this->info('Publishing CMS views...');
        $this->call('vendor:publish', ['--tag' => 'cms-views']);

        $this->info('Publishing CMS language files...');
        $this->call('vendor:publish', ['--tag' => 'cms-lang']);

        $this->info('Publishing required package migrations...');

        // Publish Spatie Permission migrations
        $this->call('vendor:publish', [
            '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            '--tag' => 'permission-migrations',
        ]);

        // Publish Curator media migrations
        $this->call('vendor:publish', [
            '--provider' => 'Awcodes\Curator\CuratorServiceProvider',
            '--tag' => 'curator-migrations',
        ]);

        // Publish SEO Suite migrations
        $this->call('vendor:publish', [
            '--provider' => 'Littleboy130491\SeoSuite\SeoSuiteServiceProvider',
            '--tag' => 'seo-suite-migrations',
        ]);

        // Publish Filament Breezy migrations
        $this->call('vendor:publish', [
            '--provider' => 'Jeffgreco13\FilamentBreezy\BreezyServiceProvider',
            '--tag' => 'filament-breezy-migrations',
        ]);

        // Publish Filament Menu Builder migrations
        $this->call('vendor:publish', [
            '--tag' => 'filament-menu-builder-migrations',
        ]);

        if ($this->confirm('Do you want to run the migrations now?', true)) {
            $this->info('Running migrations...');
            $this->call('migrate');
        }

        if ($this->confirm('Do you want to install Filament panels?', true)) {
            $this->info('Installing Filament panels...');
            $this->call('filament:install', ['--panels' => true]);
        }

        if ($this->confirm('Do you want to create an admin user?', true)) {
            $this->info('Creating admin user...');
            $this->call('make:filament-user');
        }

        if ($this->confirm('Do you want to install Filament Shield?', true)) {
            $this->info('Installing Filament Shield...');
            $this->call('shield:install');
        }

        if ($this->confirm('Do you want to generate default permission roles?', true)) {
            $this->info('Generating permission roles...');
            $this->call('cms:generate-roles');
        }

        $this->output->success('CMS has been installed successfully! 🎉');
        $this->line('');
        $this->info('✅ The CMS plugin has been automatically registered with your Filament panels!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Visit /admin and log in to access the CMS');
        $this->line('2. Configure your CMS settings in the admin panel');
        $this->line('3. Create your first content (pages, posts, etc.)');
        $this->line('4. Customize views and components as needed');
        $this->line('');
        $this->info('Available commands:');
        $this->line('- php artisan cms:create-model');
        $this->line('- php artisan cms:create-migration');
        $this->line('- php artisan cms:generate-sitemap');
        $this->line('- php artisan cms:publish-scheduled-content');
    }
}
