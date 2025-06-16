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
        $panelPath = app_path('Providers/Filament/AdminPanelProvider.php');

        if (! file_exists($panelPath)) {
            $this->error('Filament Admin Panel is not installed yet. Please run "php artisan cms:install" first.');

            return;
        }

        $this->info('=== Finalizing CMS Setup ===');

        // 0. Register the plugin
        $this->registerSumimasenPlugin($panelPath);

        // 1. Create admin user
        if ($this->confirm('Do you want to create an admin user?', true)) {
            $this->info('Creating admin user...');
            $this->call('make:filament-user');
        }

        // 2. Install Filament Shield
        if ($this->confirm('Do you want to install Filament Shield?', true)) {
            $this->info('Installing Filament Shield...');
            $this->call('shield:install');
            $this->call('shield:super-admin');
            $this->call('shield:publish', ['panel' => 'admin']);
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

    private function registerSumimasenPlugin(string $panelPath): void
    {
        $pluginImport = 'use Littleboy130491\Sumimasen\SumimasenPlugin;';
        $pluginCall = 'SumimasenPlugin::make(),';

        $file = file_get_contents($panelPath);

        // 1. Add import if missing
        if (strpos($file, $pluginImport) === false) {
            // Insert after the last existing use statement
            $file = preg_replace(
                '/^(use [^\n]+;\n)+/m',
                "$0$pluginImport\n",
                $file,
                1,
            );

            // If no use statements exist, add after namespace
            if (strpos($file, $pluginImport) === false) {
                $file = preg_replace(
                    '/(namespace [^\n]+;)/',
                    "$1\n\n$pluginImport",
                    $file,
                    1,
                );
            }
        }

        // 2. Add plugin to plugins([]) call if missing
        if (strpos($file, $pluginCall) === false) {
            $file = preg_replace_callback(
                '/->plugins\(\s*\[\s*((?:[^\]]|\](?!\)))*?)\s*\]\s*\)/s',
                function ($matches) use ($pluginCall) {
                    $existing = rtrim($matches[1]);
                    if ($existing !== '' && ! str_ends_with(trim($existing), ',')) {
                        $existing .= ',';
                    }

                    return "->plugins([\n                $existing\n                $pluginCall\n            ])";
                },
                $file,
                1
            );
        }

        file_put_contents($panelPath, $file);
        $this->info('SumimasenPlugin registered in AdminPanelProvider.php');
    }
}
