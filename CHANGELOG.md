# Changelog

All notable changes to `laravel-model-vectorize` will be documented in this file.

## [2.0.0] - 2026-04-24

### Breaking Changes
- **Removed `key` metadata index requirement**: Model keys are now extracted directly from the vector ID format (e.g., `App_Models_Product_123`)
- This provides cleaner metadata and reduced storage requirements
- Users who have created a `key` metadata index can safely delete it
- Existing vector data will continue to work as the ID format has not changed

### Added
- Five new artisan commands for index management:
  - `vectorize:create-index` - Create a new Vectorize index via API
  - `vectorize:drop-index` - Delete a Vectorize index
  - `vectorize:create-metadata-index` - Create metadata indexes for filtering
  - `vectorize:delete-metadata-index` - Delete metadata indexes
  - `vectorize:list-metadata-indexes` - List all metadata indexes
- New methods in `VectorizeClient`:
  - `createIndex()` - Create a new index via Cloudflare API
  - `deleteIndex()` - Delete an index
  - `indexExists()` - Check if an index exists
  - `createMetadataIndex()` - Create metadata index
  - `deleteMetadataIndex()` - Delete metadata index
  - `listMetadataIndexes()` - List all metadata indexes
- Improved documentation with artisan command examples
- Setup now possible entirely via artisan commands without Wrangler CLI

### Changed
- `VectorizeEngine::mapIds()` now extracts model keys from vector ID using regex instead of metadata
- `VectorizeBuilder::mapResultsToModels()` updated to extract keys from vector ID format
- Fixed namespace in `VectorizeEngine` from `ScoutVectorize\Engines` to `BrynjDigital\LaravelModelVectorize\Engines`

### Migration Guide
For existing installations upgrading from 1.x to 2.0:

1. The `key` metadata index is no longer required. You can optionally delete it:
   ```bash
   php artisan vectorize:delete-metadata-index key
   ```
2. No changes to existing vector data are required - the package now extracts keys from the vector ID format
3. Update your documentation/setup scripts to use the new artisan commands instead of Wrangler CLI

## [1.0.0] - Initial Release

### Added
- Initial release
- Cloudflare Vectorize integration for Laravel models
- Support for semantic search using vector embeddings
- Automatic embedding generation using Cloudflare Workers AI
- Batch indexing and deletion operations
- Three artisan commands:
  - `vectorize:import` - Import all records of a model
  - `vectorize:flush` - Flush all vectors for a specific model
  - `vectorize:info` - Display index information
- Support for custom `toSearchableText()` method on models
- Automatic model prefixing for multi-model indexes
- Configuration file for Cloudflare credentials and embedding model selection
- Comprehensive documentation and usage examples
- VectorSearchable trait for easy model integration
- Queue support for background processing
- Event dispatching for model indexing operations

### Features
- PHP 8.1+ support
- Laravel 10.x, 11.x, and 12.x compatibility
- Multiple embedding model support (@cf/baai/bge-small/base/large-en-v1.5)
- Metadata support for model identification and filtering
- Fluent query builder with `where()` clause support
