<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait para centralizar la lógica común de DataTables
 * Usado en controllers que manejan índices con paginación, búsqueda y ordenamiento
 */
trait HasDataTableFeatures
{
    /**
     * Obtiene los parámetros de paginación y ordenamiento del request
     *
     * @param  Request  $request
     * @return array
     */
    protected function getPaginationParams(Request $request): array
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $sortCriteria = $request->get('sort_criteria');

        // Parse multiple sort criteria if provided
        $multipleSortCriteria = [];
        if ($sortCriteria) {
            $decoded = json_decode($sortCriteria, true);
            if (is_array($decoded)) {
                $multipleSortCriteria = $decoded;
            }
        }

        return [
            'search' => $search,
            'per_page' => (int) $perPage,
            'page' => (int) $page,
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
            'sort_criteria' => $sortCriteria,
            'multiple_sort_criteria' => $multipleSortCriteria,
        ];
    }

    /**
     * Aplica búsqueda a un query basado en campos searchables
     *
     * @param  Builder  $query
     * @param  string  $searchTerm
     * @param  array  $searchableFields  - Array de campos o callbacks
     * @return Builder
     *
     * @example
     * $this->applySearch($query, 'john', [
     *     'name',
     *     'email',
     *     'roles' => function($q) use ($search) {
     *         $q->where('name', 'like', "%{$search}%");
     *     }
     * ]);
     */
    protected function applySearch(Builder $query, string $searchTerm, array $searchableFields): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm, $searchableFields) {
            foreach ($searchableFields as $key => $field) {
                // Si es una relación con callback
                if (is_string($key) && is_callable($field)) {
                    $q->orWhereHas($key, function ($subQuery) use ($field, $searchTerm) {
                        $field($subQuery, $searchTerm);
                    });
                }
                // Si es un campo simple
                elseif (is_string($field)) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            }
        });
    }

    /**
     * Aplica ordenamiento múltiple al query
     *
     * @param  Builder  $query
     * @param  array  $sortCriteria  - Array de criterios [{field, direction}]
     * @param  array  $fieldMappings  - Mapeo de campos virtuales a campos reales
     * @return Builder
     *
     * @example
     * $this->applyMultipleSorting($query, $criteria, [
     *     'user' => 'name',
     *     'status' => 'CASE WHEN last_activity_at >= ... THEN 1 ELSE 2 END'
     * ]);
     */
    protected function applyMultipleSorting(Builder $query, array $sortCriteria, array $fieldMappings = []): Builder
    {
        if (empty($sortCriteria)) {
            return $query;
        }

        foreach ($sortCriteria as $criteria) {
            $field = $criteria['field'] ?? 'created_at';
            $direction = $criteria['direction'] ?? 'desc';

            // Verificar si hay un mapeo para este campo
            if (isset($fieldMappings[$field])) {
                $mappedField = $fieldMappings[$field];

                // Si el mapeo es una expresión SQL cruda (contiene espacios o paréntesis)
                if (str_contains($mappedField, ' ') || str_contains($mappedField, '(')) {
                    $query->orderByRaw("{$mappedField} ".strtoupper($direction));
                } else {
                    $query->orderBy($mappedField, $direction);
                }
            } else {
                // Campo directo sin mapeo
                $query->orderBy($field, $direction);
            }
        }

        return $query;
    }

    /**
     * Aplica ordenamiento simple al query
     *
     * @param  Builder  $query
     * @param  string  $sortField
     * @param  string  $sortDirection
     * @param  array  $fieldMappings  - Mapeo de campos virtuales a campos reales
     * @param  string|null  $defaultSort  - Ordenamiento por defecto si no hay mapeo
     * @return Builder
     */
    protected function applySorting(
        Builder $query,
        string $sortField,
        string $sortDirection,
        array $fieldMappings = [],
        ?string $defaultSort = null
    ): Builder {
        // Verificar si hay un mapeo para este campo
        if (isset($fieldMappings[$sortField])) {
            $mappedField = $fieldMappings[$sortField];

            // Si el mapeo es una expresión SQL cruda
            if (str_contains($mappedField, ' ') || str_contains($mappedField, '(')) {
                return $query->orderByRaw("{$mappedField} ".strtoupper($sortDirection));
            } else {
                return $query->orderBy($mappedField, $sortDirection);
            }
        }

        // Campo directo sin mapeo
        if (in_array($sortField, $this->allowedSortFields ?? [])) {
            return $query->orderBy($sortField, $sortDirection);
        }

        // Aplicar ordenamiento por defecto si se proporciona
        if ($defaultSort) {
            return $query->orderByRaw($defaultSort);
        }

        // Fallback a created_at desc
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Construye la expresión SQL para ordenar por status de usuario
     * (online, recent, offline, never)
     *
     * @param  string  $direction
     * @return string
     */
    protected function getStatusSortExpression(string $direction = 'asc'): string
    {
        $dir = strtoupper($direction);

        return "
            CASE
                WHEN last_activity_at IS NULL THEN 4
                WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1
                WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 2
                ELSE 3
            END {$dir}
        ";
    }

    /**
     * Construye la respuesta para filtros que se debe enviar al frontend
     *
     * @param  array  $params  - Parámetros de paginación obtenidos con getPaginationParams()
     * @return array
     */
    protected function buildFiltersResponse(array $params): array
    {
        return [
            'search' => $params['search'],
            'per_page' => $params['per_page'],
            'page' => $params['page'],
            'sort_field' => $params['sort_field'],
            'sort_direction' => $params['sort_direction'],
            'sort_criteria' => $params['multiple_sort_criteria'],
        ];
    }
}
