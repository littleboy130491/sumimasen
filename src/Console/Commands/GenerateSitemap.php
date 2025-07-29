<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sitemap = Sitemap::create();
        $locales = array_keys(config('cms.language_available'));
        $models = config('cms.content_models');

        foreach ($models as $key => $definition) {

            $modelClass = $definition['model'];
            $slugField = 'slug'; // assuming 'slug' is the field used for all
            if ($key === config('cms.static_page_slug')) {
                $routePrefix = ''; // assuming 'page_slug' is the field used for pages
            } else {
                if ($definition['has_single']) {
                    $routePrefix = $definition['slug'] ?? $key;
                    $routePrefix = $routePrefix.'/';
                }
            }

            // Add archive page URL if has_archive is true
            if (isset($definition['has_archive']) && $definition['has_archive'] === true) {
                $archiveSlug = $definition['slug'] ?? $key;
                foreach ($locales as $locale) {
                    $archiveUrl = url("{$locale}/{$archiveSlug}");
                    $this->info("Adding archive: $archiveUrl");
                    $sitemap->add(Url::create($archiveUrl));
                }
            }

            $instance = new $modelClass;
            $query = $modelClass::query();

            if (Schema::hasColumn($instance->getTable(), 'status')) {
                $query->where('status', 'published');
            }

            $records = $query->get();

            foreach ($records as $record) {
                foreach ($locales as $locale) {
                    $slug = $record->getTranslation($slugField, $locale, false);
                    if ($slug) {
                        $url = url("{$locale}/{$routePrefix}{$slug}");
                        $this->info("Adding: $url");
                        $sitemap->add(Url::create($url));
                    } else {
                        $this->warn("Missing slug for {$locale} on record ID {$record->id} in {$modelClass}");
                    }
                }
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
        $this->info('Sitemap generated successfully.');
    }
}
