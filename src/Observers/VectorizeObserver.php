<?php

namespace BrynjDigital\LaravelModelVectorize\Observers;

use BrynjDigital\LaravelModelVectorize\Jobs\SyncModelToVectorize;
use BrynjDigital\LaravelModelVectorize\VectorizeManager;
use Illuminate\Database\Eloquent\Model;

class VectorizeObserver
{
    public function __construct(
        protected VectorizeManager $manager
    ) {}

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        if (!$this->shouldSync($model)) {
            return;
        }

        if (config('vectorize.queue.enabled')) {
            dispatch(new SyncModelToVectorize($model, 'update'));
        } else {
            $this->manager->update($model);
        }
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        if (!$this->shouldSync($model)) {
            return;
        }

        if (config('vectorize.queue.enabled')) {
            dispatch(new SyncModelToVectorize($model, 'update'));
        } else {
            $this->manager->update($model);
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if (!$model->syncToVectorizeAutomatically()) {
            return;
        }

        if (config('vectorize.queue.enabled')) {
            dispatch(new SyncModelToVectorize($model, 'delete'));
        } else {
            $this->manager->delete($model);
        }
    }

    /**
     * Determine if the model should be synced.
     */
    protected function shouldSync(Model $model): bool
    {
        return $model->syncToVectorizeAutomatically()
            && $model->shouldBeSearchable();
    }
}
