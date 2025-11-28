<?php

namespace BrynjDigital\LaravelModelVectorize\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ModelRemovedFromIndex
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Model $model,
        public string $vectorizeId
    ) {}
}
