<?php

namespace BrynjDigital\LaravelModelVectorize;

use BrynjDigital\LaravelModelVectorize\Console\FlushCommand;
use BrynjDigital\LaravelModelVectorize\Console\ImportCommand;
use BrynjDigital\LaravelModelVectorize\Console\IndexInfoCommand;
use BrynjDigital\LaravelModelVectorize\Console\CreateIndexCommand;
use BrynjDigital\LaravelModelVectorize\Console\DropIndexCommand;
use BrynjDigital\LaravelModelVectorize\Console\CreateMetadataIndexCommand;
use BrynjDigital\LaravelModelVectorize\Console\DeleteMetadataIndexCommand;
use BrynjDigital\LaravelModelVectorize\Console\ListMetadataIndexesCommand;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class VectorizeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/vectorize.php',
            'vectorize'
        );

        // Register VectorizeClient singleton
        $this->app->singleton(VectorizeClient::class, function ($app) {
            return new VectorizeClient(
                accountId: config('vectorize.cloudflare.account_id'),
                apiToken: config('vectorize.cloudflare.api_token'),
                indexName: config('vectorize.index'),
                embeddingModel: config('vectorize.embedding_model')
            );
        });

        // Register VectorizeManager singleton
        $this->app->singleton(VectorizeManager::class, function ($app) {
            return new VectorizeManager(
                client: $app->make(VectorizeClient::class),
                events: $app->make(Dispatcher::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/vectorize.php' => config_path('vectorize.php'),
        ], 'vectorize-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCommand::class,
                FlushCommand::class,
                IndexInfoCommand::class,
                CreateIndexCommand::class,
                DropIndexCommand::class,
                CreateMetadataIndexCommand::class,
                DeleteMetadataIndexCommand::class,
                ListMetadataIndexesCommand::class,
            ]);
        }
    }
}
