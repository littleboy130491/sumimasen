<?php

namespace Littleboy130491\Sumimasen\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Littleboy130491\Sumimasen\Services\DebugCollector;

class DebugServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $debugConfig = config('cms.debug_mode');

        // Only boot if debug mode is enabled and environment is allowed
        if (!$debugConfig['enabled'] || !in_array(app()->environment(), $debugConfig['environments'])) {
            return;
        }

        // View Composer to collect view data
        View::composer('*', function ($view) {
            if (app()->bound('debug.collector')) {
                app('debug.collector')->addViewData(
                    $view->getName(),
                    $view->getData()
                );
            }
        });

        // Database Query Listener
        if ($debugConfig['include_queries']) {
            DB::listen(function ($query) {
                if (app()->bound('debug.collector')) {
                    app('debug.collector')->addQueryData([
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // Cache Hit/Miss Tracking
        if ($debugConfig['include_cache_info']) {
            Event::listen(CacheHit::class, function (CacheHit $event) {
                if (app()->bound('debug.collector')) {
                    app('debug.collector')->addCacheData(true, $event->key);
                }
            });

            Event::listen(CacheMissed::class, function (CacheMissed $event) {
                if (app()->bound('debug.collector')) {
                    app('debug.collector')->addCacheData(false, $event->key);
                }
            });
        }
    }
}