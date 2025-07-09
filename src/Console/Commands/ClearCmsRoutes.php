<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ClearCmsRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:routes-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear both Laravel route cache and CMS route cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Clear Laravel's route cache
        $this->info('Clearing Laravel route cache...');
        Artisan::call('route:clear');

        // Clear CMS route caches
        $this->info('Clearing CMS route cache...');
        Cache::forget('cms.route_slugs');

        $this->info('Clearing CMS slug-to-key mapping cache...');
        Cache::forget('cms.slug_to_key_map');

        $this->info('Clearing CMS key-to-config mapping cache...');
        Cache::forget('cms.key_to_config_map');

        $this->info('All route caches cleared successfully!');

        return Command::SUCCESS;
    }
}