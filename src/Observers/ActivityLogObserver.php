<?php

namespace Littleboy130491\Sumimasen\Observers;

use Illuminate\Database\Eloquent\Model;
use Littleboy130491\Sumimasen\Services\ActivityLogger;

class ActivityLogObserver
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function created(Model $model): void
    {
        $this->activityLogger->logCreate($model, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->activityLogger->logUpdate($model, $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->activityLogger->logDelete($model);
    }

    public function restored(Model $model): void
    {
        $this->activityLogger->log('resource_restored', [
            'model_class' => get_class($model),
            'restored_at' => now()->toDateTimeString(),
        ], $model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->activityLogger->log('resource_force_deleted', [
            'model_class' => get_class($model),
            'force_deleted_at' => now()->toDateTimeString(),
        ], $model);
    }
}