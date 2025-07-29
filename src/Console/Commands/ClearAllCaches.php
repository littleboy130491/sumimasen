<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ClearAllCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:clear-all-caches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all cache types (application, config, view, route, response, and CMS caches)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cache clearing process...');

        // Clear Laravel caches
        $this->info('Clearing application cache...');
        Artisan::call('cache:clear');

        $this->info('Clearing configuration cache...');
        Artisan::call('config:clear');

        $this->info('Clearing view cache...');
        Artisan::call('view:clear');

        $this->info('Clearing route cache...');
        Artisan::call('route:clear');

        // Clear response cache (if available)
        $this->info('Clearing response cache...');
        try {
            Artisan::call('responsecache:clear');
        } catch (\Exception $e) {
            $this->warn('Response cache clearing failed: '.$e->getMessage());
        }

        // Clear CMS specific caches
        $this->info('Clearing CMS route cache...');
        Cache::forget('cms.route_slugs');
        Cache::forget('cms.slug_to_key_map');
        Cache::forget('cms.key_to_config_map');

        // Clear any other common cache keys that might be used
        $this->info('Clearing additional CMS caches...');
        $cmsKeys = [
            'cms.settings',
            'cms.navigation',
            'cms.menu',
            'cms.sitemap',
            'cms.pages',
            'cms.posts',
        ];

        foreach ($cmsKeys as $key) {
            Cache::forget($key);
        }

        $this->info('All caches cleared successfully!');

        return Command::SUCCESS;
    }
}
