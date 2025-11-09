<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FilterBuilder
{
    protected Builder $query;
    protected array $filters;
    protected bool $useOrWhere = false;

    /**
     * Create a new FilterBuilder instance.
     *
     * @param Builder $query The base query builder
     * @param array|string $filters The filters to apply (array or JSON string)
     */
    public function __construct(Builder $query, array|string $filters)
    {
        $this->query = $query;
        
        // If filters is a JSON string, decode it
        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }
        
        $this->filters = $filters ?? [];
    }

    /**
     * Apply all filters to the query.
     *
     * @return Builder
     */
    public function apply(): Builder
    {
        foreach ($this->filters as $field => $value) {
            $this->applyFilter($field, $value);
        }

        return $this->query;
    }

    /**
     * Apply a single filter.
     *
     * @param string $field The field name (may contain dot notation)
     * @param mixed $value The value to filter by
     * @return void
     */
    protected function applyFilter(string $field, mixed $value): void
    {
        // Get the model name from the query
        $modelName = strtolower(class_basename($this->query->getModel()));
        
        // If field starts with model name (e.g., "appointment.status"), remove the prefix
        if (str_starts_with(strtolower($field), $modelName . '.')) {
            $field = substr($field, strlen($modelName) + 1);
        }
        
        // Check if field contains dot notation (relationship)
        if (str_contains($field, '.')) {
            $this->applyRelationshipFilter($field, $value);
        } else {
            // Apply filter directly on the current model
            $this->query->where($field, $value);
        }
    }

    /**
     * Apply a filter on a relationship using dot notation.
     *
     * @param string $field The field with dot notation (e.g., "patient.name")
     * @param mixed $value The value to filter by
     * @return void
     */
    protected function applyRelationshipFilter(string $field, mixed $value): void
    {
        $parts = explode('.', $field);
        $relationship = $parts[0];
        $relationshipField = implode('.', array_slice($parts, 1));

        // Get the model from the query
        $model = $this->query->getModel();
        
        // Check if the relationship exists
        if (!method_exists($model, $relationship)) {
            // Fallback: try to apply as a direct field if relationship doesn't exist
            $this->query->where($field, $value);
            return;
        }

        // Determine if we should use whereHas or orWhereHas
        if ($this->useOrWhere) {
            $this->query->orWhereHas($relationship, function ($query) use ($relationshipField, $value) {
                $this->applyNestedFilter($query, $relationshipField, $value);
            });
        } else {
            $this->query->whereHas($relationship, function ($query) use ($relationshipField, $value) {
                $this->applyNestedFilter($query, $relationshipField, $value);
            });
        }
    }

    /**
     * Apply a nested filter (handles multiple levels of relationships).
     *
     * @param Builder $query The query builder
     * @param string $field The field path (may contain more dots)
     * @param mixed $value The value to filter by
     * @return void
     */
    protected function applyNestedFilter(Builder $query, string $field, mixed $value): void
    {
        // If there are more dots, it's a nested relationship
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $relationship = $parts[0];
            $relationshipField = implode('.', array_slice($parts, 1));

            $model = $query->getModel();
            
            if (method_exists($model, $relationship)) {
                $query->whereHas($relationship, function ($nestedQuery) use ($relationshipField, $value) {
                    $this->applyNestedFilter($nestedQuery, $relationshipField, $value);
                });
            } else {
                // Fallback to direct field
                $query->where($field, $value);
            }
        } else {
            // This is the final field, apply the where clause
            $query->where($field, $value);
        }
    }

    /**
     * Set whether to use OR conditions for subsequent filters.
     *
     * @param bool $useOr
     * @return self
     */
    public function useOrWhere(bool $useOr = true): self
    {
        $this->useOrWhere = $useOr;
        return $this;
    }

    /**
     * Static factory method for easier usage.
     *
     * @param Builder $query
     * @param array|string $filters
     * @return Builder
     */
    public static function applyFilters(Builder $query, array|string $filters): Builder
    {
        return (new self($query, $filters))->apply();
    }
}

