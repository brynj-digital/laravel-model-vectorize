<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Cloudflare account credentials for accessing the
    | Vectorize API and Workers AI for embeddings generation.
    |
    */

    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vectorize Index
    |--------------------------------------------------------------------------
    |
    | The name of your Cloudflare Vectorize index. This index must be
    | created via the Cloudflare dashboard or API before use.
    |
    */

    'index' => env('CLOUDFLARE_VECTORIZE_INDEX', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Model
    |--------------------------------------------------------------------------
    |
    | The Cloudflare Workers AI embedding model to use. Options:
    | - @cf/baai/bge-small-en-v1.5 (384 dimensions)
    | - @cf/baai/bge-base-en-v1.5 (768 dimensions) [default]
    | - @cf/baai/bge-large-en-v1.5 (1024 dimensions)
    |
    */

    'embedding_model' => env('CLOUDFLARE_EMBEDDING_MODEL', '@cf/baai/bge-base-en-v1.5'),

    /*
    |--------------------------------------------------------------------------
    | Synchronization Settings
    |--------------------------------------------------------------------------
    |
    | Configure how models are synchronized to Vectorize.
    |
    */

    'sync' => [
        'automatically' => env('VECTORIZE_AUTO_SYNC', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, all indexing operations will be queued for background
    | processing. This can improve performance for high-volume applications.
    |
    */

    'queue' => [
        'enabled' => env('VECTORIZE_QUEUE', false),
        'connection' => env('VECTORIZE_QUEUE_CONNECTION', null),
        'queue' => env('VECTORIZE_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Processing
    |--------------------------------------------------------------------------
    |
    | The number of models to process in a single batch during bulk operations
    | like imports and flushes.
    |
    */

    'batch_size' => env('VECTORIZE_BATCH_SIZE', 100),

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Configure timeouts and other API-related settings.
    |
    */

    'timeout' => env('VECTORIZE_TIMEOUT', 30),

];
