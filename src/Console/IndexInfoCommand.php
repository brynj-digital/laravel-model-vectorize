<?php

namespace BrynjDigital\LaravelModelVectorize\Console;

use BrynjDigital\LaravelModelVectorize\VectorizeClient;
use Illuminate\Console\Command;

class IndexInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vectorize:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display Vectorize index information';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $client = app(VectorizeClient::class);

        $this->info('Vectorize Index Information');
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['Index Name', config('vectorize.index')],
                ['Embedding Model', config('vectorize.embedding_model')],
                ['Auto-sync', config('vectorize.sync.automatically') ? 'Enabled' : 'Disabled'],
                ['Queue', config('vectorize.queue.enabled') ? 'Enabled' : 'Disabled'],
                ['Batch Size', config('vectorize.batch_size', 100)],
            ]
        );

        return 0;
    }
}
