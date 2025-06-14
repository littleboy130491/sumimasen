<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;

class CmsFinalizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:finalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finalize CMS installation: create admin user, install Shield, and set up roles.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!file_exists(app_path('Providers/Filament/AdminPanelProvider.php'))) {
            $this->error('Filament Admin Panel is not installed yet. Please run "php artisan cms:install" first.');
            return;
        }

        $this->info('=== Finalizing CMS Setup ===');

        // 1. Create admin user
        if ($this->confirm('Do you want to create an admin user?', true)) {
            $this->info('Creating admin user...');
            $this->call('make:filament-user');
        }

        // 2. Install Filament Shield
        if ($this->confirm('Do you want to install Filament Shield?', true)) {
            $this->info('Installing Filament Shield...');
            $this->call('shield:install');
        }

        // 3. Generate default permission roles
        if ($this->confirm('Do you want to generate default permission roles?', true)) {
            $this->info('Generating permission roles...');
            $this->call('cms:generate-roles');
        }

        $this->output->success('CMS finalization complete! 🎉');
        $this->line('');
        $this->info('✅ You can now log in at /admin with your admin account.');
        $this->line('Visit your Filament panel and configure your CMS as needed.');
    }
}
