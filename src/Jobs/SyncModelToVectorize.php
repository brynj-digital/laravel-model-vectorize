<?php

namespace BrynjDigital\LaravelModelVectorize\Jobs;

use BrynjDigital\LaravelModelVectorize\VectorizeManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncModelToVectorize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Model $model,
        public string $action  // 'update' or 'delete'
    ) {
        $this->onQueue(config('vectorize.queue.queue', 'default'));

        if ($connection = config('vectorize.queue.connection')) {
            $this->onConnection($connection);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(VectorizeManager $manager): void
    {
        match($this->action) {
            'update' => $manager->update($this->model),
            'delete' => $manager->delete($this->model),
            default => null,
        };
    }
}
