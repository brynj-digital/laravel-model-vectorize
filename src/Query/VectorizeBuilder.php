<?php

namespace BrynjDigital\LaravelModelVectorize\Query;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use BrynjDigital\LaravelModelVectorize\VectorizeManager;

class VectorizeBuilder
{
    protected int $limit = 10;
    protected array $wheres = [];

    public function __construct(
        protected Model $model,
        protected string $query,
        protected ?Closure $callback = null
    ) {}

    /**
     * Add a metadata filter to the query.
     */
    public function where(string $key, mixed $value): self
    {
        $this->wheres[$key] = $value;
        return $this;
    }

    /**
     * Set the maximum number of results to return.
     */
    public function take(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get the results as Eloquent models.
     */
    public function get(): Collection
    {
        $results = $this->execute();

        return $this->mapResultsToModels($results);
    }

    /**
     * Get the results as a lazy collection.
     */
    public function cursor(): LazyCollection
    {
        $results = $this->execute();

        $models = $this->mapResultsToModels($results);

        return LazyCollection::make($models);
    }

    /**
     * Get raw results with scores.
     */
    public function raw(): array
    {
        $results = $this->execute();

        return $results['results'] ?? [];
    }

    /**
     * Get the first result.
     */
    public function first(): ?Model
    {
        $this->limit = 1;
        $results = $this->get();

        return $results->first();
    }

    /**
     * Get the count of results.
     */
    public function count(): int
    {
        $results = $this->execute();

        return $results['total'] ?? 0;
    }

    /**
     * Execute the search query.
     */
    protected function execute(): array
    {
        $manager = app(VectorizeManager::class);

        return $manager->search($this->query, $this->model, [
            'limit' => $this->limit,
            'filter' => $this->buildFilter(),
            'callback' => $this->callback,
        ]);
    }

    /**
     * Build the metadata filter.
     */
    protected function buildFilter(): array
    {
        // Always include model class filter
        return array_merge(
            ['model' => get_class($this->model)],
            $this->wheres
        );
    }

    /**
     * Map results to Eloquent models.
     */
    protected function mapResultsToModels(array $results): Collection
    {
        if (empty($results['results'])) {
            return $this->model->newCollection();
        }

        // Extract model IDs from vector ID format
        // Format: "App_Models_Product_123" -> 123
        $modelIds = collect($results['results'])->map(function ($result) {
            $id = $result['id'] ?? '';
            if (preg_match('/_(\d+)$/', $id, $matches)) {
                return (int) $matches[1];
            }
            return null;
        })->filter()->values()->all();

        if (empty($modelIds)) {
            return $this->model->newCollection();
        }

        // Create position mapping to maintain order
        $positions = array_flip($modelIds);

        // Fetch models from database
        $models = $this->model->newQuery()
            ->whereIn($this->model->getKeyName(), $modelIds)
            ->get();

        // Sort by original result order (similarity scores)
        return $models->sortBy(function ($model) use ($positions) {
            return $positions[$model->getKey()] ?? 999;
        })->values();
    }
}
