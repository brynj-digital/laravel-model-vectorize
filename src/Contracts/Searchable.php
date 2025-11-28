<?php

namespace BrynjDigital\LaravelModelVectorize\Contracts;

interface Searchable
{
    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array;

    /**
     * Get the searchable text for the model.
     */
    public function toSearchableText(): ?string;

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool;

    /**
     * Determine if the model should be synced to Vectorize automatically.
     */
    public function syncToVectorizeAutomatically(): bool;
}
