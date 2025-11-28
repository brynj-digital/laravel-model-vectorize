# Laravel Model Vectorize

A standalone Laravel package for semantic search with [Cloudflare Vectorize](https://developers.cloudflare.com/vectorize/), independent of Laravel Scout.

## Features

- **Semantic Search**: Search by meaning, not just keywords
- **Scout-Independent**: No Scout dependency required
- **Auto-Sync**: Automatic indexing via Eloquent observers
- **Queue Support**: Background processing for better performance
- **Multiple Models**: Support for searching across different Eloquent models
- **Cloudflare Workers AI**: Automatic embedding generation
- **Simple API**: Clean, Laravel-idiomatic interface

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- A [Cloudflare account](https://dash.cloudflare.com/) with Vectorize enabled
- Cloudflare API token with Vectorize permissions

## Installation

Install the package via Composer:

```bash
composer require brynj-digital/laravel-model-vectorize
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=vectorize-config
```

## Configuration

### 1. Create a Vectorize Index

Create a Vectorize index in your Cloudflare dashboard or via the API:

```bash
# Using Wrangler CLI
npx wrangler vectorize create my-index --dimensions=768 --metric=cosine
```

The dimensions must match your chosen embedding model:
- `@cf/baai/bge-small-en-v1.5`: 384 dimensions
- `@cf/baai/bge-base-en-v1.5`: 768 dimensions (default)
- `@cf/baai/bge-large-en-v1.5`: 1024 dimensions

### 2. Create Metadata Indexes

Create metadata indexes to enable efficient filtering. The `model` and `key` indexes are **required**:

```bash
# Required: Create metadata index for model filtering
npx wrangler vectorize create-metadata-index my-index --property-name=model --type=string

# Required: Create metadata index for key filtering
npx wrangler vectorize create-metadata-index my-index --property-name=key --type=number
```

#### Optional: Additional Metadata Indexes

Create indexes for any custom fields you want to filter on:

```bash
# Example: Create index for filtering by status
npx wrangler vectorize create-metadata-index my-index --property-name=status --type=string

# Example: Create index for filtering by category_id
npx wrangler vectorize create-metadata-index my-index --property-name=category_id --type=number
```

### 3. Environment Variables

Add the following to your `.env` file:

```env
CLOUDFLARE_ACCOUNT_ID=your_account_id
CLOUDFLARE_API_TOKEN=your_api_token
CLOUDFLARE_VECTORIZE_INDEX=my-index
CLOUDFLARE_EMBEDDING_MODEL=@cf/baai/bge-base-en-v1.5

# Optional: Queue configuration
VECTORIZE_QUEUE=true
VECTORIZE_QUEUE_CONNECTION=redis
VECTORIZE_QUEUE_NAME=vectorize
```

## Usage

### Basic Model Setup

Add the `VectorSearchable` trait to your model:

```php
use BrynjDigital\LaravelModelVectorize\Traits\VectorSearchable;

class Product extends Model
{
    use VectorSearchable;

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'category' => $this->category,
        ];
    }
}
```

### Custom Text Conversion (Optional)

For more control over how your model is converted to searchable text, implement a `toSearchableText()` method:

```php
class Product extends Model
{
    use VectorSearchable;

    /**
     * Convert the model to searchable text.
     * This method takes precedence over toSearchableArray().
     */
    public function toSearchableText(): string
    {
        return "{$this->name}. {$this->brand}. {$this->description}";
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
        ];
    }
}
```

### Searching

```php
// Simple search
$products = Product::vectorSearch('wireless headphones')->get();

// With limit
$products = Product::vectorSearch('laptop')->take(20)->get();

// With filters (metadata-based)
$products = Product::vectorSearch('gaming laptop')
    ->where('status', 'published')
    ->where('in_stock', true)
    ->get();

// Get raw results with scores
$results = Product::vectorSearch('laptop')->raw();
// Returns: [['id' => ..., 'score' => 0.95, 'metadata' => [...]], ...]

// Lazy loading
$products = Product::vectorSearch('tablet')->cursor()->each(function ($product) {
    // Process each result
});

// First result only
$product = Product::vectorSearch('macbook pro')->first();

// Custom callback
$products = Product::vectorSearch('laptop', function($client, $query) {
    return $client->search($query, 50, ['custom' => 'filter']);
})->get();
```

### Manual Syncing

```php
// Sync single model
$product->syncToVectorize();

// Remove from index
$product->removeFromVectorize();

// Bulk operations
Product::makeAllSearchableInVectorize();
Product::removeAllFromVectorize();
```

### Artisan Commands

```bash
# Import all products
php artisan vectorize:import "App\Models\Product"

# Flush all products
php artisan vectorize:flush "App\Models\Product"

# Display index info
php artisan vectorize:info
```

### Model Observers

The package automatically syncs your models when you create, update, or delete them:

```php
// Automatically indexed
$product = Product::create([
    'name' => 'Wireless Headphones',
    'description' => 'High-quality Bluetooth headphones',
]);

// Automatically re-indexed
$product->update(['name' => 'Premium Wireless Headphones']);

// Automatically removed from index
$product->delete();
```

### Customizing Sync Behavior

```php
class Product extends Model
{
    use VectorSearchable;

    /**
     * Determine if this model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Disable auto-sync for this model.
     */
    public function syncToVectorizeAutomatically(): bool
    {
        return false;
    }
}
```

## Configuration Reference

```php
// config/vectorize.php

return [
    // Cloudflare credentials
    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
    ],

    // Index configuration
    'index' => env('CLOUDFLARE_VECTORIZE_INDEX', 'default'),
    'embedding_model' => env('CLOUDFLARE_EMBEDDING_MODEL', '@cf/baai/bge-base-en-v1.5'),

    // Synchronization settings
    'sync' => [
        'automatically' => env('VECTORIZE_AUTO_SYNC', true),
    ],

    // Queue configuration
    'queue' => [
        'enabled' => env('VECTORIZE_QUEUE', false),
        'connection' => env('VECTORIZE_QUEUE_CONNECTION', null),
        'queue' => env('VECTORIZE_QUEUE_NAME', 'default'),
    ],

    // Batch processing
    'batch_size' => env('VECTORIZE_BATCH_SIZE', 100),

    // API settings
    'timeout' => env('VECTORIZE_TIMEOUT', 30),
];
```

## How It Works

1. **Indexing**: When a model is indexed:
   - Calls `toSearchableText()` or flattens `toSearchableArray()` to text
   - Generates an embedding using Cloudflare Workers AI
   - Stores the vector in Cloudflare Vectorize with metadata

2. **Searching**: When you search:
   - Your query text is converted to an embedding
   - Vectorize finds the most similar vectors
   - Results are mapped back to your Eloquent models
   - Models are fetched from your database and returned

3. **Vector IDs**: The package prefixes vector IDs with the model class name to support multiple model types in one index (e.g., `App_Models_Product_123`)

## Events

The package dispatches events after operations:

```php
use BrynjDigital\LaravelModelVectorize\Events\ModelIndexed;
use BrynjDigital\LaravelModelVectorize\Events\ModelRemovedFromIndex;

// Listen to events
Event::listen(ModelIndexed::class, function ($event) {
    // $event->model
    // $event->vectorData
});

Event::listen(ModelRemovedFromIndex::class, function ($event) {
    // $event->model
    // $event->vectorizeId
});
```

## Best Practices

### Optimizing Search Quality

1. **Use descriptive text**: Include context in your searchable content
   ```php
   public function toSearchableText(): string
   {
       return "Product: {$this->name}. Brand: {$this->brand}. {$this->description}";
   }
   ```

2. **Avoid overly long text**: Embeddings work best with focused, relevant content
3. **Include relevant metadata**: Add fields you'll filter on to `toSearchableArray()`

### Performance Tips

1. **Enable queueing for production**: Prevent blocking requests
2. **Use batch operations**: Import in bulk with artisan commands
3. **Limit search results**: Only fetch what you need with `take()`
4. **Cache frequent queries**: Use Laravel's cache for popular searches

## License

This package is open-source software licensed under the [MIT license](LICENSE.md).

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/brynj-digital/laravel-model-vectorize).
