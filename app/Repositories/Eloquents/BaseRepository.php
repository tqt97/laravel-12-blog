<?php

namespace App\Repositories\Eloquents;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * Constructor.
     *
     * @param  Model  $model  The Eloquent model instance used for queries.
     */
    public function __construct(protected Model $model) {}

    /* -------------------------------------------------------------------------
     | Retrieval Methods
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
        // Initialize a query with specified columns and apply eager loading of relationships if provided
        $query = $this->applyRelations($this->model->newQuery()->select($columns), $relations);

        // Apply filtering conditions if provided
        if (! empty($conditions)) {
            $query = $this->applyConditions($query, $conditions);
        }

        // Apply sorting criteria if provided
        if (! empty($sorts)) {
            $query = $this->applySort($query, $sorts);
        }

        // Execute the query and return the result as a collection
        return $query->get();
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
        $query = $this->applyRelations($this->model->newQuery()->select($columns), $relations);
        if (! empty($conditions)) {
            $query = $this->applyConditions($query, $conditions);
        }
        if (! empty($sorts)) {
            $query = $this->applySort($query, $sorts);
        }

        return $query->paginate($perPage);
    }

    /**
     * Retrieve the first record.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Model|null The first record found or null if not found.
     */
    public function first(array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->applyRelations($this->model->newQuery()->select($columns), $relations)->first();
    }

    /**
     * Retrieve the first record or fail.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail(array $columns = ['*'], array $relations = []): Model
    {
        return $this->applyRelations($this->model->newQuery()->select($columns), $relations)->firstOrFail();
    }

    /**
     * Find a record by its ID.
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
        return $this->applyRelations($this->model->newQuery()->select($columns), $relations)->find($id);
    }

    /**
     * Find a record by specified conditions.
     *
     * @param  array  $conditions  The conditions for filtering.
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return Model|null The record found or null if not found.
     */
    public function findBy(
        array $conditions,
        array $columns = ['*'],
        array $relations = []
    ): ?Model {
        return $this->applyRelations($this->model->newQuery()->where($conditions)->select($columns), $relations)->first();
    }

    /**
     * Check if any records exist based on given conditions.
     *
     * @param  array  $conditions  The conditions for filtering.
     * @return bool True if at least one record exists, false otherwise.
     */
    public function exists(array $conditions): bool
    {
        return $this->model->newQuery()->where($conditions)->exists();
    }

    /**
     * Count the number of records based on given conditions.
     *
     * @param  array  $conditions  The conditions for filtering.
     * @return int The count of records.
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();
        // Apply the conditions to the query if provided
        if (! empty($conditions)) {
            $query = $this->applyConditions($query, $conditions);
        }

        // Execute the query and return the count
        return $query->count();
    }

    /**
     * Retrieve records by applying filter criteria with optional eager loading.
     *
     * @param  array  $filters  The filter criteria as key-value pairs.
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return \Illuminate\Support\Collection A collection of filtered records.
     */
    public function filter(
        array $filters,
        array $columns = ['*'],
        array $relations = []
    ): Collection {
        // Initialize the query with selected columns and apply any eager loading of relations
        $query = $this->applyRelations($this->model->newQuery()->select($columns), $relations);

        // Apply each filter condition to the query
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        // Execute the query and return the results
        return $query->get();
    }

    /**
     * Retrieve all records and transform each record.
     *
     * @param  array  $columns  The columns to select.
     * @param  array  $relations  The relationships to eager load.
     * @return \Illuminate\Support\Collection A collection of transformed records.
     */
    public function allTransformed(
        array $columns = ['*'],
        array $relations = []
    ): Collection {
        return $this->all($columns, $relations)
            ->map(fn (Model $item) => $this->transform($item));
    }

    /**
     * Retrieve values of a given column.
     *
     * @param  string  $column  The column name.
     * @param  string|null  $key  The column to use as the array key.
     */
    public function pluck(string $column, ?string $key = null, bool $cache = true): Collection
    {
        return $this->model->newQuery()->pluck($column, $key);
    }

    /* -------------------------------------------------------------------------
     | Write Methods (Using Model Instance from Route Binding)
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
        return $this->model->create($data);
    }

    /**
     * Update the given model instance.
     *
     * If no attributes are changed (i.e. the model is clean), returns true immediately.
     * Otherwise, saves the updated model and clears the related cache.
     *
     * @param  Model  $model  The model instance to update.
     * @param  array  $data  Data to update the model.
     * @return bool True if the update was successful or no changes were needed.
     */
    public function update(Model $model, array $data): bool
    {
        $model->fill($data);
        if ($model->isClean()) {
            // If no changes have been made, return true immediately.
            return true;
        }

        // Save the updated model and clear the related cache.
        return $model->save();
    }

    /**
     * Update records that match the given conditions.
     *
     * @param  array  $conditions  Conditions to identify records.
     * @param  array  $data  Data to update the records.
     * @return int Number of records updated.
     */
    public function updateBy(array $conditions, array $data): int
    {
        // Create a new query and apply the given conditions
        // and update the records with the given data.
        return $this->model->newQuery()->where($conditions)->update($data);
    }

    /**
     * Delete the given model instance.
     *
     * @param  Model  $model  The model instance to delete.
     * @return bool True if deletion was successful.
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Delete records that match the given conditions.
     *
     * @param  array  $conditions  Conditions to identify records.
     * @return int Number of records deleted.
     */
    public function deleteBy(array $conditions): int
    {
        // Create a new query and apply the given conditions
        return $this->model->newQuery()->where($conditions)->delete();
    }

    /**
     * Force delete a soft-deleted model instance.
     *
     * @param  Model  $model  The model instance to force delete.
     * @return bool True if force deletion was successful.
     */
    public function forceDelete(Model $model): bool
    {
        return $model->forceDelete();
    }

    /**
     * Force delete records that match the given conditions.
     *
     * @param  array  $conditions  The conditions to identify records.
     * @return int The number of records force deleted.
     */
    public function forceDeleteBy(array $conditions): int
    {
        return $this->model->newQuery()->where($conditions)->forceDelete();
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @param  Model  $model  The model instance to restore.
     * @return bool True if restoration was successful.
     */
    public function restore(Model $model): bool
    {
        return $model->restore();
    }

    /**
     * Retrieve all records including soft-deleted ones, with optional filtering, sorting, and eager loading.
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
        // Initialize a query with trashed records and select specified columns
        $query = $this->applyRelations($this->model->newQuery()->withTrashed()->select($columns), $relations);

        // Apply filtering conditions if provided
        if (! empty($conditions)) {
            $query = $this->applyConditions($query, $conditions);
        }

        // Apply sorting criteria if provided
        if (! empty($sorts)) {
            $query = $this->applySort($query, $sorts);
        }

        // Execute the query and return the results as a collection
        return $query->get();
    }

    /**
     * Retrieve only soft-deleted records.
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
        $query = $this->applyRelations($this->model->newQuery()->onlyTrashed()->select($columns), $relations);
        if (! empty($conditions)) {
            $query = $this->applyConditions($query, $conditions);
        }
        if (! empty($sorts)) {
            $query = $this->applySort($query, $sorts);
        }

        return $query->get();
    }

    /**
     * Load relationships for a given model instance.
     *
     * @param  Model  $model  The model instance.
     * @param  array  $relations  The relationships to load.
     * @return Model The model with loaded relationships.
     */
    public function loadRelations(Model $model, array $relations): Model
    {
        return $model->load($relations);
    }

    /**
     * Transform the given model instance to an array.
     *
     * @param  Model  $model  The model instance.
     * @return array The transformed array.
     */
    public function transform(Model $model): array
    {
        return $model->toArray();
    }

    /* -------------------------------------------------------------------------
     | Additional Methods Using Eloquent Helpers
     | -------------------------------------------------------------------------
     */

    /**
     * Retrieve the first record matching the attributes or create it.
     *
     * @param  array  $attributes  Attributes to search for.
     * @param  array  $values  Additional attributes for creation.
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->firstOrCreate($attributes, $values);
    }

    /**
     * Update an existing record matching the attributes or create it.
     *
     * @param  array  $attributes  Attributes to search for.
     * @param  array  $values  Data to update or create.
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Perform an upsert operation.
     *
     * @param  array  $values  Array of records to insert or update.
     * @param  array  $uniqueBy  The column(s) that uniquely identify records.
     * @param  array|null  $update  Columns to update if record exists.
     * @return int Number of affected rows.
     */
    public function upsert(array $values, array $uniqueBy, ?array $update = null): int
    {
        // Note: Upsert returns void in some Laravel versions. Adjust the return type if needed.
        return $this->model->newQuery()->upsert($values, $uniqueBy, $update);
    }

    /**
     * Sync a many-to-many relationship for the given model.
     *
     * @param  Model  $model  The model instance.
     * @param  string  $relation  The relationship name.
     * @param  array  $ids  IDs to sync.
     * @param  bool  $detaching  Whether to detach missing IDs.
     * @return array The sync result (attached, detached, updated).
     */
    public function sync(Model $model, string $relation, array $ids, bool $detaching = true): array
    {
        return $model->$relation()->sync($ids, $detaching);
    }

    /**
     * Detach related models from a many-to-many relationship.
     *
     * @param  Model  $model  The model instance.
     * @param  string  $relation  The relationship name.
     * @param  mixed|null  $ids  IDs to detach. If null, detach all.
     * @return int Number of detached records.
     */
    public function detach(Model $model, string $relation, $ids = null): int
    {
        return $model->$relation()->detach($ids);
    }

    /* -------------------------------------------------------------------------
     | Protected Helper Methods
     | -------------------------------------------------------------------------
     */

    /**
     * Apply eager loading of relationships to the query.
     */
    protected function applyRelations(Builder $query, array $relations): Builder
    {
        return ! empty($relations) ? $query->with($relations) : $query;
    }

    /**
     * Apply filtering conditions to the query.
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
     */
    protected function applySort(Builder $query, array $sorts): Builder
    {
        foreach ($sorts as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }
}
