<?php

namespace BrynjDigital\LaravelModelVectorize\Console;

use BrynjDigital\LaravelModelVectorize\VectorizeManager;
use Illuminate\Console\Command;

class FlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vectorize:flush {model : The model class to flush}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all models of a type from Vectorize';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelClass = $this->argument('model');

        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");
            return 1;
        }

        if (!$this->confirm("Remove all {$modelClass} from Vectorize?")) {
            return 1;
        }

        $this->info("Flushing {$modelClass} from Vectorize...");

        app(VectorizeManager::class)->flush($modelClass);

        $this->info("✓ Flushed all {$modelClass} records");

        return 0;
    }
}
