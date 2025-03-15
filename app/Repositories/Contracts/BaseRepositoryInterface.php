<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Retrieve all records.
     *
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     * @param  array  $conditions  Conditions for filtering.
     * @param  array  $sorts  Sorting criteria.
     */
    public function all(
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): Collection;

    /**
     * Retrieve paginated records.
     *
     * @param  int  $perPage  Number of records per page.
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     * @param  array  $conditions  Conditions for filtering.
     * @param  array  $sorts  Sorting criteria.
     */
    public function paginate(
        int $perPage = 10,
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): LengthAwarePaginator;

    /**
     * Retrieve the first record.
     *
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     */
    public function first(array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Retrieve the first record or fail.
     *
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail(array $columns = ['*'], array $relations = []): Model;

    /**
     * Find a record by its ID.
     *
     * @param  int  $id  The ID of the record.
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     */
    public function find(
        int $id,
        array $columns = ['*'],
        array $relations = []
    ): ?Model;

    /**
     * Find a record by specified conditions.
     *
     * @param  array  $conditions  Conditions for filtering.
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     */
    public function findBy(
        array $conditions,
        array $columns = ['*'],
        array $relations = []
    ): ?Model;

    /**
     * Check if any records exist based on given conditions.
     *
     * @param  array  $conditions  Conditions for filtering.
     */
    public function exists(array $conditions): bool;

    /**
     * Create a new record.
     *
     * @param  array  $data  Data for the new record.
     */
    public function create(array $data): Model;

    /**
     * Update the given model instance.
     *
     * If no attributes are changed, returns true immediately.
     *
     * @param  Model  $model  The model instance to update.
     * @param  array  $data  Data to update the model.
     * @return bool True if update was successful or no changes were needed.
     */
    public function update(Model $model, array $data): bool;

    /**
     * Update records that match the given conditions.
     *
     * @param  array  $conditions  Conditions to identify records.
     * @param  array  $data  Data to update.
     * @return int Number of records updated.
     */
    public function updateBy(array $conditions, array $data): int;

    /**
     * Delete the given model instance.
     *
     * @param  Model  $model  The model instance to delete.
     * @return bool True if deletion was successful.
     */
    public function delete(Model $model): bool;

    /**
     * Delete records that match the given conditions.
     *
     * @param  array  $conditions  Conditions to identify records.
     * @return int Number of records deleted.
     */
    public function deleteBy(array $conditions): int;

    /**
     * Force delete the given model instance.
     *
     * @param  Model  $model  The model instance to force delete.
     * @return bool True if force deletion was successful.
     */
    public function forceDelete(Model $model): bool;

    /**
     * Force delete records that match the given conditions.
     *
     * @param  array  $conditions  Conditions to identify records.
     * @return int Number of records force deleted.
     */
    public function forceDeleteBy(array $conditions): int;

    /**
     * Restore the given soft-deleted model instance.
     *
     * @param  Model  $model  The model instance to restore.
     * @return bool True if restoration was successful.
     */
    public function restore(Model $model): bool;

    /**
     * Retrieve all records including soft-deleted ones.
     *
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     * @param  array  $conditions  Conditions for filtering.
     * @param  array  $sorts  Sorting criteria.
     */
    public function allWithTrashed(
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): Collection;

    /**
     * Retrieve only soft-deleted records.
     *
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     * @param  array  $conditions  Conditions for filtering.
     * @param  array  $sorts  Sorting criteria.
     */
    public function onlyTrashed(
        array $columns = ['*'],
        array $relations = [],
        array $conditions = [],
        array $sorts = []
    ): Collection;

    /**
     * Load relationships for a given model instance.
     *
     * @param  Model  $model  The model instance.
     * @param  array  $relations  Relationships to load.
     * @return Model The model with loaded relationships.
     */
    public function loadRelations(Model $model, array $relations): Model;

    /**
     * Transform a model instance to an array.
     *
     * @param  Model  $model  The model instance.
     */
    public function transform(Model $model): array;

    /**
     * Retrieve all records and transform each record.
     *
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     */
    public function allTransformed(
        array $columns = ['*'],
        array $relations = []
    ): Collection;

    /**
     * Count the number of records based on given conditions.
     *
     * @param  array  $conditions  Conditions for filtering.
     */
    public function count(array $conditions = []): int;

    /**
     * Retrieve records based on filter criteria.
     *
     * @param  array  $filters  Filter criteria.
     * @param  array  $columns  Columns to select.
     * @param  array  $relations  Relationships to eager load.
     */
    public function filter(
        array $filters,
        array $columns = ['*'],
        array $relations = []
    ): Collection;

    /**
     * Retrieve the first record that matches attributes, or create it.
     *
     * @param  array  $attributes  Attributes to search for.
     * @param  array  $values  Additional attributes to create if not found.
     */
    public function firstOrCreate(array $attributes, array $values = []): Model;

    /**
     * Update an existing record matching attributes, or create it.
     *
     * @param  array  $attributes  Attributes to search for.
     * @param  array  $values  Data to update or create.
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Perform an upsert operation.
     *
     * @param  array  $values  Array of records to insert or update.
     * @param  array  $uniqueBy  The column(s) that uniquely identify records.
     * @param  array|null  $update  Columns to update if record exists.
     * @return int Number of affected rows.
     */
    public function upsert(array $values, array $uniqueBy, ?array $update = null): int;

    /**
     * Sync a many-to-many relationship on the given model.
     *
     * @param  Model  $model  The model instance.
     * @param  string  $relation  The relationship name.
     * @param  array  $ids  IDs to sync.
     * @param  bool  $detaching  Whether to detach missing IDs.
     * @return array The sync result (attached, detached, updated).
     */
    public function sync(Model $model, string $relation, array $ids, bool $detaching = true): array;

    /**
     * Detach related models from a many-to-many relationship.
     *
     * @param  Model  $model  The model instance.
     * @param  string  $relation  The relationship name.
     * @param  mixed|null  $ids  IDs to detach. If null, detach all.
     * @return int Number of detached records.
     */
    public function detach(Model $model, string $relation, $ids = null): int;

    /**
     * Retrieve values of a given column.
     *
     * @param  string  $column  The column name.
     * @param  string|null  $key  The column to use as the array key.
     */
    public function pluck(string $column, ?string $key = null, bool $cache = true): Collection;
}
