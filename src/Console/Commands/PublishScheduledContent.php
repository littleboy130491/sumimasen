<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Littleboy130491\Sumimasen\Enums\ContentStatus;

class PublishScheduledContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes scheduled content based on published_at date.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $contentModels = Config::get('cms.content_models');

        foreach ($contentModels as $modelConfig) {
            if (isset($modelConfig['type']) && $modelConfig['type'] === 'content') {
                $modelClass = $modelConfig['model'];

                // Check if the model class exists and has the necessary columns (status and published_at)
                $instance = new $modelClass;
                $tableName = $instance->getTable();

                // Check if the model class exists, is an Eloquent model, and has the necessary columns
                if (
                    class_exists($modelClass) &&
                    is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class) && // Check if it's an Eloquent model
                    Schema::hasColumn($tableName, 'status') &&
                    Schema::hasColumn($tableName, 'published_at')
                ) {
                    $modelClass::where('status', ContentStatus::Scheduled)
                        ->where('published_at', '<=', now())
                        ->update(['status' => ContentStatus::Published]);

                    $this->info("Published scheduled content for model: {$modelClass}");
                } else {
                    $this->warn("Skipping model {$modelClass}: Class does not exist, is not a valid Eloquent model, or is missing required columns (status, published_at).");
                }
            }
        }

        $this->info('Scheduled content publishing complete.');
    }
}
