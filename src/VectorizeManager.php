<?php

namespace BrynjDigital\LaravelModelVectorize;

use BrynjDigital\LaravelModelVectorize\Events\ModelIndexed;
use BrynjDigital\LaravelModelVectorize\Events\ModelRemovedFromIndex;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class VectorizeManager
{
    public function __construct(
        protected VectorizeClient $client,
        protected Dispatcher $events
    ) {}

    /**
     * Update a single model in the index.
     */
    public function update(Model $model): void
    {
        $document = $this->makeDocument($model);

        if ($document === null) {
            return;
        }

        $this->client->batchUpsert([$document]);

        $this->events->dispatch(new ModelIndexed($model, $document));
    }

    /**
     * Update multiple models in the index.
     */
    public function updateMany(Collection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $documents = $models->map(function ($model) {
            return $this->makeDocument($model);
        })->filter()->values()->all();

        if (empty($documents)) {
            return;
        }

        $this->client->batchUpsert($documents);

        foreach ($models as $model) {
            $this->events->dispatch(new ModelIndexed($model, []));
        }
    }

    /**
     * Delete a single model from the index.
     */
    public function delete(Model $model): void
    {
        $vectorizeId = $this->getVectorizeId($model);

        $this->client->deleteVectors([$vectorizeId]);

        $this->events->dispatch(new ModelRemovedFromIndex($model, $vectorizeId));
    }

    /**
     * Delete multiple models from the index.
     */
    public function deleteMany(Collection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $ids = $models->map(fn($model) => $this->getVectorizeId($model))->all();

        $this->client->deleteVectors($ids);

        foreach ($models as $model) {
            $this->events->dispatch(new ModelRemovedFromIndex($model, $this->getVectorizeId($model)));
        }
    }

    /**
     * Flush all vectors for a given model class from the index.
     */
    public function flush(string $modelClass): void
    {
        // Get embedding dimensions from the model
        $embeddingModel = $this->client->getEmbeddingModel();
        $dimensions = $this->getModelDimensions($embeddingModel);

        // Create a dummy embedding vector
        $dummyVector = array_fill(0, $dimensions, 0.0);

        $deletedIds = [];

        // Keep querying and deleting until no vectors remain for this model
        while (true) {
            // Query for next batch of vectors with model filter
            $result = $this->client->queryVectors($dummyVector, 100, [
                'model' => $modelClass,
            ]);

            if (!isset($result['result']['matches']) || count($result['result']['matches']) === 0) {
                break;
            }

            // Filter out already-deleted IDs
            $matches = array_filter($result['result']['matches'], function($match) use ($deletedIds) {
                return !in_array($match['id'], $deletedIds);
            });

            if (empty($matches)) {
                break;
            }

            $ids = array_column($matches, 'id');

            $this->client->deleteVectors($ids);
            $deletedIds = array_merge($deletedIds, $ids);
        }
    }

    /**
     * Search for models using a text query.
     */
    public function search(string $query, Model $model, array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $filter = $options['filter'] ?? ['model' => get_class($model)];
        $callback = $options['callback'] ?? null;

        if ($callback) {
            return call_user_func($callback, $this->client, $query, $options);
        }

        // Perform the vector search
        $results = $this->client->search(
            query: $query,
            topK: $limit,
            filter: $filter
        );

        return [
            'results' => $results,
            'total' => count($results),
        ];
    }

    /**
     * Create a document from a model.
     */
    protected function makeDocument(Model $model): ?array
    {
        $searchableData = $model->toSearchableArray();

        if (empty($searchableData)) {
            return null;
        }

        // Convert searchable array to text for embedding
        $text = $this->convertToSearchableText($model, $searchableData);

        // Prepare metadata - include all searchable data plus required fields
        $metadata = array_merge(
            $searchableData,
            [
                'model' => get_class($model),
                'key' => $model->getKey(),
            ]
        );

        return [
            'id' => $this->getVectorizeId($model),
            'text' => $text,
            'metadata' => $metadata,
        ];
    }

    /**
     * Convert model data to searchable text.
     */
    protected function convertToSearchableText(Model $model, array $searchableData): string
    {
        // If model has a custom toSearchableText method, use that
        if (method_exists($model, 'toSearchableText')) {
            $text = $model->toSearchableText();
            if (!empty($text)) {
                return $text;
            }
        }

        // Otherwise, flatten the searchable array
        return collect($searchableData)
            ->filter()
            ->map(function ($value) {
                if (is_array($value)) {
                    return implode(' ', array_filter($value));
                }
                return $value;
            })
            ->filter()
            ->implode('. ');
    }

    /**
     * Get the Vectorize ID for a model.
     */
    protected function getVectorizeId(Model $model): string
    {
        $class = str_replace('\\', '_', get_class($model));
        return "{$class}_{$model->getKey()}";
    }

    /**
     * Extract the model key from a Vectorize ID.
     */
    protected function extractModelKey(string $vectorizeId): mixed
    {
        // Format: "App_Models_Product_123" -> "123"
        $parts = explode('_', $vectorizeId);
        return end($parts);
    }

    /**
     * Get the dimensions for a given embedding model.
     */
    protected function getModelDimensions(string $model): int
    {
        return match($model) {
            '@cf/baai/bge-small-en-v1.5' => 384,
            '@cf/baai/bge-base-en-v1.5' => 768,
            '@cf/baai/bge-large-en-v1.5' => 1024,
            default => 768,
        };
    }
}
