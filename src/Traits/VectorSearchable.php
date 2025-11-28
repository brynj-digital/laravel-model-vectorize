<?php

namespace BrynjDigital\LaravelModelVectorize\Traits;

use BrynjDigital\LaravelModelVectorize\Jobs\SyncModelToVectorize;
use BrynjDigital\LaravelModelVectorize\Observers\VectorizeObserver;
use BrynjDigital\LaravelModelVectorize\Query\VectorizeBuilder;
use BrynjDigital\LaravelModelVectorize\VectorizeManager;
use Closure;
use Illuminate\Database\Eloquent\Collection;

trait VectorSearchable
{
    /**
     * Boot the trait and register the observer.
     */
    public static function bootVectorSearchable(): void
    {
        static::observe(VectorizeObserver::class);
    }

    /**
     * Perform a vector search.
     */
    public static function vectorSearch(string $query, ?Closure $callback = null): VectorizeBuilder
    {
        return new VectorizeBuilder(new static, $query, $callback);
    }

    /**
     * Sync this model to Vectorize.
     */
    public function syncToVectorize(): void
    {
        if (config('vectorize.queue.enabled')) {
            $this->queueSyncToVectorize();
        } else {
            app(VectorizeManager::class)->update($this);
        }
    }

    /**
     * Remove this model from Vectorize.
     */
    public function removeFromVectorize(): void
    {
        if (config('vectorize.queue.enabled')) {
            $this->queueRemoveFromVectorize();
        } else {
            app(VectorizeManager::class)->delete($this);
        }
    }

    /**
     * Make all instances of this model searchable in Vectorize.
     */
    public static function makeAllSearchableInVectorize(): void
    {
        $manager = app(VectorizeManager::class);
        $batchSize = config('vectorize.batch_size', 100);

        static::query()
            ->when(method_exists(static::class, 'shouldBeSearchable'), function ($query) {
                // Apply shouldBeSearchable scope if available
            })
            ->chunk($batchSize, function (Collection $models) use ($manager) {
                $manager->updateMany($models);
            });
    }

    /**
     * Remove all instances of this model from Vectorize.
     */
    public static function removeAllFromVectorize(): void
    {
        $manager = app(VectorizeManager::class);
        $manager->flush(static::class);
    }

    /**
     * Determine if the model should be synced to Vectorize automatically.
     */
    public function syncToVectorizeAutomatically(): bool
    {
        return config('vectorize.sync.automatically', true);
    }

    /**
     * Get the events that should trigger syncing to Vectorize.
     */
    public function searchableEvents(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    /**
     * Determine if this model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * Get the indexable data array for the model.
     * This method should be implemented by the model.
     */
    abstract public function toSearchableArray(): array;

    /**
     * Get the searchable text for the model.
     * This method can be optionally implemented by the model.
     */
    public function toSearchableText(): ?string
    {
        return null;
    }

    /**
     * Get the Vectorize key for the model.
     */
    public function getVectorizeKey(): string
    {
        $class = str_replace('\\', '_', get_class($this));
        return "{$class}_{$this->getKey()}";
    }

    /**
     * Queue the model for syncing to Vectorize.
     */
    protected function queueSyncToVectorize(): void
    {
        dispatch(new SyncModelToVectorize($this, 'update'));
    }

    /**
     * Queue the model for removal from Vectorize.
     */
    protected function queueRemoveFromVectorize(): void
    {
        dispatch(new SyncModelToVectorize($this, 'delete'));
    }
}
