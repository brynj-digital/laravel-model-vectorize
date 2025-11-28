<?php

namespace BrynjDigital\LaravelModelVectorize\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ModelIndexed
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Model $model,
        public array $vectorData
    ) {}
}
