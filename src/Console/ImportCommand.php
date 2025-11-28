<?php

namespace BrynjDigital\LaravelModelVectorize\Console;

use BrynjDigital\LaravelModelVectorize\VectorizeManager;
use Illuminate\Console\Command;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vectorize:import {model : The model class to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all models of a type into Vectorize';

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

        $batchSize = config('vectorize.batch_size', 100);
        $manager = app(VectorizeManager::class);

        $this->info("Importing {$modelClass} to Vectorize...");

        $count = 0;
        $modelClass::chunk($batchSize, function ($models) use ($manager, &$count) {
            $manager->updateMany($models);
            $count += $models->count();
            $this->info("Indexed {$count} records...");
        });

        $this->info("✓ Imported {$count} {$modelClass} records");

        return 0;
    }
}
