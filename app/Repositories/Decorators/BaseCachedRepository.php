<?php

namespace App\Repositories\Decorators;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\Cache\CacheServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BaseCachedRepository implements BaseRepositoryInterface
{
    protected BaseRepositoryInterface $repository;

    protected CacheServiceInterface $cacheService;

    protected int $cacheTTL;

    protected string $cacheKeyPrefix;

    /**
     * Constructor.
     *
     * @param  BaseRepositoryInterface  $repository  The repository instance to be decorated.
     * @param  CacheServiceInterface  $cacheService  The cache service used for caching.
     * @param  int|null  $cacheTTL  Optional override for cache TTL (in seconds). If null, the default TTL is used.
     */
    public function __construct(BaseRepositoryInterface $repository, CacheServiceInterface $cacheService, ?int $cacheTTL = null)
    {
        $this->repository = $repository;
        $this->cacheService = $cacheService;
        $this->cacheTTL = $cacheTTL ?? $this->cacheService->getDefaultTTL();
        // Use the repository's class basename as the cache key prefix.
        $this->cacheKeyPrefix = class_basename($this->repository);
    }

    /**
     * Generate a cache key based on the method name and its arguments.
     *
     * @param  string  $method  The name of the method being called.
     * @param  array  $args  The arguments passed to the method.
     * @return string The generated cache key.
     */
    protected function getCacheKey(string $method, array $args): string
    {
        $args = array_map(function ($arg) {
            if (is_object($arg)) {
                if (method_exists($arg, 'toArray')) {
                    return $arg->toArray();
                } else {
                    $vars = get_object_vars($arg);
                    // If no public properties are available, use the object's hash.
                    return !empty($vars) ? $vars : spl_object_hash($arg);
                }
            }
            return $arg;
        }, $args);
        $args = array_filter($args, fn ($arg) => ! is_null($arg) && $arg !== []);
        ksort($args);

        return sprintf('%s:%s_%s', $this->cacheKeyPrefix, $method, md5(json_encode($args)));
    }

    /**
     * Clear all cache entries associated with this repository using its tag.
     */
    protected function clearCache(): void
    {
        $this->cacheService->flushByTag($this->cacheKeyPrefix);
    }

    /* -------------------------------------------------------------------------
        | Retrieval Methods (Cached)
        | -------------------------------------------------------------------------
        */

    /**
     * Retrieve all records with optional filtering, sorting, and eager loading.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @param  array  $conditions  The conditions for filtering records.
     * @param  array  $sorts  The sorting criteria.
     * @return Collection A collection of records.
     */
    public function all(
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): Collection {
        $cacheKey = $this->getCacheKey('all', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($columns, $relations, $conditions, $sorts) {
            return $this->repository->all($columns, $relations, $conditions, $sorts);
        });
    }

    /**
     * Retrieve paginated records with optional filtering, sorting, and eager loading.
     *
     * @param  int  $perPage  The number of records per page.
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @param  array  $conditions  The conditions for filtering records.
     * @param  array  $sorts  The sorting criteria.
     * @return LengthAwarePaginator A paginator instance containing the records.
     */
    public function paginate(
        int $perPage = 10,
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): LengthAwarePaginator {
        $cacheKey = $this->getCacheKey('paginate', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($perPage, $columns, $relations, $conditions, $sorts) {
            return $this->repository->paginate($perPage, $columns, $relations, $conditions, $sorts);
        });
    }

    /**
     * Retrieve the first record with optional eager loading.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Model|null The first record found or null if none exists.
     */
    public function first(array $columns = ['*'], array $relations = []): ?Model
    {
        $cacheKey = $this->getCacheKey('first', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($columns, $relations) {
            return $this->repository->first($columns, $relations);
        });
    }

    /**
     * Retrieve the first record with optional eager loading or throw an exception if not found.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Model The first record found.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If no record is found.
     */
    public function firstOrFail(array $columns = ['*'], array $relations = []): Model
    {
        $cacheKey = $this->getCacheKey('firstOrFail', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($columns, $relations) {
            return $this->repository->firstOrFail($columns, $relations);
        });
    }

    /**
     * Find a record by its ID with optional eager loading.
     *
     * @param  int  $id  The ID of the record.
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Model|null The record found or null if not found.
     */
    public function find(
        int $id,
        array $columns = ['*'],
        array $relations = []
    ): ?Model {
        $cacheKey = $this->getCacheKey('find', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($id, $columns, $relations) {
            return $this->repository->find($id, $columns, $relations);
        });
    }

    /**
     * Find a record by specified conditions with optional eager loading.
     *
     * @param  array  $conditions  The conditions for filtering records.
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Model|null The record found or null if not found.
     */
    public function findBy(array $conditions, array $columns = ['*'], array $relations = []): ?Model
    {
        $cacheKey = $this->getCacheKey('findBy', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($conditions, $columns, $relations) {
            return $this->repository->findBy($conditions, $columns, $relations);
        });
    }

    /**
     * Check if any records exist based on given conditions.
     *
     * @param  array  $conditions  The conditions for filtering.
     * @return bool True if at least one record exists, false otherwise.
     */
    public function exists(array $conditions): bool
    {
        $cacheKey = $this->getCacheKey('exists', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($conditions) {
            return $this->repository->exists($conditions);
        });
    }

    /**
     * Count the number of records based on given conditions.
     *
     * @param  array  $conditions  The conditions for filtering.
     * @return int The count of records.
     */
    public function count(array $conditions = []): int
    {
        $cacheKey = $this->getCacheKey('count', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($conditions) {
            return $this->repository->count($conditions);
        });
    }

    /**
     * Retrieve records by applying filter criteria with optional eager loading.
     *
     * @param  array  $filters  The filter criteria as key-value pairs.
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Collection A collection of filtered records.
     */
    public function filter(array $filters, array $columns = ['*'], array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('filter', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($filters, $columns, $relations) {
            return $this->repository->filter($filters, $columns, $relations);
        });
    }

    /**
     * Retrieve all records, transform each record, and return the transformed collection.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Collection A collection of transformed records.
     */
    public function allTransformed(array $columns = ['*'], array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('allTransformed', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($columns, $relations) {
            return $this->repository->allTransformed($columns, $relations);
        });
    }

    /**
     * Retrieve values of a given column from all records.
     *
     * @param  string  $column  The column name.
     * @param  string|null  $key  The column to use as the array key.
     * @return \Illuminate\Support\Collection A collection of column values.
     */
    public function pluck(string $column, ?string $key = null, bool $cache = true): Collection
    {
        if (! $cache) {
            return $this->repository->pluck($column, $key);
        }

        $cacheKey = $this->getCacheKey('pluck', [$column, $key]);

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, fn () => $this->repository->pluck($column, $key));
    }

    /**
     * Retrieve all records including soft-deleted ones with optional filtering, sorting, and eager loading.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @param  array  $conditions  The conditions for filtering records.
     * @param  array  $sorts  The sorting criteria.
     * @return Collection A collection of records including trashed ones.
     */
    public function allWithTrashed(
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): Collection {
        $cacheKey = $this->getCacheKey('allWithTrashed', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($columns, $relations, $conditions, $sorts) {
            return $this->repository->allWithTrashed($columns, $relations, $conditions, $sorts);
        });
    }

    /**
     * Retrieve only soft-deleted records with optional filtering, sorting, and eager loading.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @param  array  $conditions  The conditions for filtering records.
     * @param  array  $sorts  The sorting criteria.
     * @return Collection A collection of only trashed records.
     */
    public function onlyTrashed(
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): Collection {
        $cacheKey = $this->getCacheKey('onlyTrashed', func_get_args());

        return $this->cacheService->remember($cacheKey, $this->cacheTTL, function () use ($columns, $relations, $conditions, $sorts) {
            return $this->repository->onlyTrashed($columns, $relations, $conditions, $sorts);
        });
    }

    /* -------------------------------------------------------------------------
        | Write Methods (with Cache Invalidation)
        | -------------------------------------------------------------------------
        */

    /**
     * Create a new record.
     *
     * @param  array  $data  The data to create the record.
     * @return Model The newly created record.
     */
    public function create(array $data): Model
    {
        $this->clearCache();

        return $this->repository->create($data);
    }

    /**
     * Update the given model instance.
     *
     * If no attributes are changed (i.e. the model is clean), returns true immediately.
     * Otherwise, saves the updated model and clears the related cache.
     *
     * @param  Model  $model  The model instance to update.
     * @param  array  $data  The data to update the model.
     * @return bool True if the update was successful or no changes were needed.
     */
    public function update(Model $model, array $data): bool
    {
        $model->fill($data);
        if ($model->isClean()) {
            return true;
        }
        $result = $model->save();
        if ($result) {
            $this->clearCache();
        }

        return (bool) $result;
    }

    /**
     * Update records that match the given conditions.
     *
     * @param  array  $conditions  The conditions to identify records.
     * @param  array  $data  The data to update.
     * @return int The number of records updated.
     */
    public function updateBy(array $conditions, array $data): int
    {
        $this->clearCache();

        return $this->repository->updateBy($conditions, $data);
    }

    /**
     * Delete the given model instance.
     *
     * @param  Model  $model  The model instance to delete.
     * @return bool True if deletion was successful.
     */
    public function delete(Model $model): bool
    {
        $result = $model->delete();
        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Delete records that match the given conditions.
     *
     * @param  array  $conditions  The conditions to identify records.
     * @return int The number of records deleted.
     */
    public function deleteBy(array $conditions): int
    {
        $this->clearCache();

        return $this->repository->deleteBy($conditions);
    }

    /**
     * Force delete the given model instance.
     *
     * @param  Model  $model  The model instance to force delete.
     * @return bool True if force deletion was successful.
     */
    public function forceDelete(Model $model): bool
    {
        $result = $model->forceDelete();
        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Force delete records that match the given conditions.
     *
     * @param  array  $conditions  The conditions to identify records.
     * @return int The number of records force deleted.
     */
    public function forceDeleteBy(array $conditions): int
    {
        $this->clearCache();

        return $this->repository->forceDeleteBy($conditions);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @param  Model  $model  The model instance to restore.
     * @return bool True if restoration was successful.
     */
    public function restore(Model $model): bool
    {
        $result = $model->restore();
        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /* -------------------------------------------------------------------------
        | Additional Eloquent Helper Methods (Non-Cached)
        | -------------------------------------------------------------------------
        */

    /**
     * Retrieve the first record matching the given attributes or create it.
     *
     * @param  array  $attributes  The attributes to search for.
     * @param  array  $values  Additional attributes to use for creation.
     * @return Model The resulting record.
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $this->clearCache();

        return $this->repository->firstOrCreate($attributes, $values);
    }

    /**
     * Update an existing record matching the given attributes or create it.
     *
     * @param  array  $attributes  The attributes to search for.
     * @param  array  $values  The data to update or create.
     * @return Model The resulting record.
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $this->clearCache();

        return $this->repository->updateOrCreate($attributes, $values);
    }

    /**
     * Perform an upsert operation (insert or update multiple records).
     *
     * @param  array  $values  Array of records to insert or update.
     * @param  array  $uniqueBy  The column(s) that uniquely identify records.
     * @param  array|null  $update  The columns to update if record exists.
     * @return int The number of affected rows.
     */
    public function upsert(array $values, array $uniqueBy, ?array $update = null): int
    {
        $this->clearCache();

        return $this->repository->upsert($values, $uniqueBy, $update);
    }

    /**
     * Sync a many-to-many relationship for the given model.
     *
     * @param  Model  $model  The model instance.
     * @param  string  $relation  The relationship name.
     * @param  array  $ids  The IDs to sync.
     * @param  bool  $detaching  Whether to detach missing IDs.
     * @return array The sync result (attached, detached, updated).
     */
    public function sync(Model $model, string $relation, array $ids, bool $detaching = true): array
    {
        $this->clearCache();

        return $model->$relation()->sync($ids, $detaching);
    }

    /**
     * Detach related models from a many-to-many relationship.
     *
     * @param  Model  $model  The model instance.
     * @param  string  $relation  The relationship name.
     * @param  mixed|null  $ids  The IDs to detach. If null, detach all.
     * @return int The number of detached records.
     */
    public function detach(Model $model, string $relation, $ids = null): int
    {
        $this->clearCache();

        return $model->$relation()->detach($ids);
    }

    /* -------------------------------------------------------------------------
        | Delegated Helper Methods
        | -------------------------------------------------------------------------
        */

    /**
     * Load relationships for the given model instance.
     *
     * @param  Model  $model  The model instance.
     * @param  array  $relations  The relationships to load.
     * @return Model The model instance with loaded relationships.
     */
    public function loadRelations(Model $model, array $relations): Model
    {
        return $this->repository->loadRelations($model, $relations);
    }

    /**
     * Transform the given model instance to an array.
     *
     * @param  Model  $model  The model instance.
     * @return array The transformed array.
     */
    public function transform(Model $model): array
    {
        return $this->repository->transform($model);
    }

    /* -------------------------------------------------------------------------
        | Protected Helper Methods for Query Building
        | -------------------------------------------------------------------------
        */

    /**
     * Apply eager loading of relationships to the query.
     *
     * @param  Builder  $query  The query builder instance.
     * @param  array  $relations  The relationships to eager load.
     * @return Builder The query builder instance with relationships applied.
     */
    protected function applyRelations(Builder $query, array $relations): Builder
    {
        return ! empty($relations) ? $query->with($relations) : $query;
    }

    /**
     * Apply filtering conditions to the query.
     *
     * @param  Builder  $query  The query builder instance.
     * @param  array  $conditions  The conditions for filtering.
     * @return Builder The query builder instance with conditions applied.
     */
    protected function applyConditions(Builder $query, array $conditions): Builder
    {
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                if (count($value) === 3) {
                    [$column, $operator, $val] = $value;
                    $query->where($column, $operator, $val);
                } else {
                    foreach ($value as $column => $val) {
                        $query->where($column, $val);
                    }
                }
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * Apply sorting criteria to the query.
     *
     * @param  Builder  $query  The query builder instance.
     * @param  array  $sorts  Sorting criteria as column => direction pairs.
     * @return Builder The query builder instance with sorting applied.
     */
    protected function applySort(Builder $query, array $sorts): Builder
    {
        foreach ($sorts as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }
}
