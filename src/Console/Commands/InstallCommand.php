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
        $this->call('vendor:publish', ['--tag' => 'sumimasen-cms-config']);

        $this->info('Publishing CMS views...');
        $this->call('vendor:publish', ['--tag' => 'sumimasen-cms-views']);

        $this->info('Publishing CMS language files...');
        $this->call('vendor:publish', ['--tag' => 'sumimasen-cms-lang']);

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
            '--provider' => 'Datlechin\FilamentMenuBuilder\FilamentMenuBuilderServiceProvider',
            '--tag' => 'filament-menu-builder-migrations',
        ]);

        // Publish Spatie Laravel Settings migrations
        $this->call('vendor:publish', [
            '--provider' => 'Spatie\LaravelSettings\LaravelSettingsServiceProvider',
            '--tag' => 'migrations',
        ]);

        $this->info('Publishing CMS migrations...');
        $this->call('vendor:publish', ['--tag' => 'sumimasen-cms-migrations']);

        if ($this->confirm('Do you want to run the migrations now?', true)) {
            $this->info('Running migrations...');
            $this->call('migrate');
        }

        // Filament panel installation: must be run before anything else Filament-related!
        if ($this->confirm('Do you want to install Filament panels?', true)) {
            $this->info('Installing Filament panels...');
            $this->call('filament:install', ['--panels' => true]);

            // Tell the user to rerun the next phase and exit
            $this->warn('Filament panels have been installed!');
            $this->warn('Please run "php artisan cms:finalize" to continue setup:');
            $this->line('- Create your admin user');
            $this->line('- Install Filament Shield');
            $this->line('- Generate default permission roles');
            $this->line('');
            $this->info('You can now visit /admin once you finish finalizing.');

            return; // Exit the command here!
        }

        // If user skips panel install, print further guidance.
        $this->output->success('CMS core has been installed successfully! 🎉');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. If you did not install Filament panels above, do so now by running:');
        $this->line('   php artisan filament:install --panels');
        $this->line('2. Then run: php artisan cms:finalize');
        $this->line('');
        $this->info('Available commands:');
        $this->line('- php artisan cms:create-model');
        $this->line('- php artisan cms:create-migration');
        $this->line('- php artisan cms:generate-sitemap');
        $this->line('- php artisan cms:publish-scheduled-content');
    }
}
