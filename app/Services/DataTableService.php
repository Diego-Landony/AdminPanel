<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Servicio para construcción y configuración de DataTables
 * Proporciona métodos de alto nivel para queries complejas de índices
 */
class DataTableService
{
    /**
     * Construye un query completo para DataTable basado en configuración
     *
     * @param  array  $config  - Configuración del datatable
     *
     * @example
     * $config = [
     *     'searchable_fields' => ['name', 'email'],
     *     'sortable_fields' => ['name', 'created_at'],
     *     'field_mappings' => ['user' => 'name', 'status' => 'is_active'],
     *     'default_sort' => ['field' => 'created_at', 'direction' => 'desc'],
     * ]
     */
    public function buildQuery(Builder $query, array $config, Request $request): Builder
    {
        // Aplicar búsqueda si está presente
        if ($search = $request->get('search')) {
            $query = $this->applySearch($query, $search, $config['searchable_fields'] ?? []);
        }

        // Aplicar ordenamiento
        $sortField = $request->get('sort_field', $config['default_sort']['field'] ?? 'created_at');
        $sortDirection = $request->get('sort_direction', $config['default_sort']['direction'] ?? 'desc');
        $sortCriteria = $request->get('sort_criteria');

        if ($sortCriteria) {
            $decoded = json_decode($sortCriteria, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $query = $this->applyMultipleSorting($query, $decoded, $config['field_mappings'] ?? []);
            }
        } else {
            $query = $this->applySorting($query, $sortField, $sortDirection, $config['field_mappings'] ?? []);
        }

        return $query;
    }

    /**
     * Aplica filtros adicionales a un query
     *
     *
     * @example
     * $filters = [
     *     'status' => 'active',
     *     'role' => ['admin', 'editor'],
     *     'created_after' => '2024-01-01'
     * ]
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (empty($value)) {
                continue;
            }

            // Filtro por array (IN)
            if (is_array($value)) {
                $query->whereIn($field, $value);

                continue;
            }

            // Filtro simple
            $query->where($field, $value);
        }

        return $query;
    }

    /**
     * Obtiene estadísticas agregadas para una entidad
     *
     *
     * @example
     * $statsConfig = [
     *     'total' => ['query' => null],
     *     'active' => ['query' => fn($q) => $q->where('is_active', true)],
     *     'online' => ['query' => fn($q) => $q->where('last_activity_at', '>=', now()->subMinutes(5))],
     * ]
     */
    public function getStatsForEntity(string $modelClass, array $statsConfig): array
    {
        $stats = [];

        foreach ($statsConfig as $key => $config) {
            $query = $modelClass::query();

            if (isset($config['query']) && is_callable($config['query'])) {
                $config['query']($query);
            }

            $stats[$key] = $query->count();
        }

        return $stats;
    }

    /**
     * Aplica búsqueda a un query
     */
    protected function applySearch(Builder $query, string $searchTerm, array $searchableFields): Builder
    {
        if (empty($searchTerm) || empty($searchableFields)) {
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
     * Aplica ordenamiento múltiple
     */
    protected function applyMultipleSorting(Builder $query, array $sortCriteria, array $fieldMappings): Builder
    {
        foreach ($sortCriteria as $criteria) {
            $field = $criteria['field'] ?? 'created_at';
            $direction = $criteria['direction'] ?? 'desc';

            // Verificar si hay un mapeo para este campo
            if (isset($fieldMappings[$field])) {
                $mappedField = $fieldMappings[$field];

                // Si el mapeo es una expresión SQL cruda
                if (str_contains($mappedField, ' ') || str_contains($mappedField, '(')) {
                    $query->orderByRaw("{$mappedField} ".strtoupper($direction));
                } else {
                    $query->orderBy($mappedField, $direction);
                }
            } else {
                $query->orderBy($field, $direction);
            }
        }

        return $query;
    }

    /**
     * Aplica ordenamiento simple
     */
    protected function applySorting(
        Builder $query,
        string $sortField,
        string $sortDirection,
        array $fieldMappings
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
        return $query->orderBy($sortField, $sortDirection);
    }

    /**
     * Prepara los datos de paginación para el frontend
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     */
    public function preparePaginationResponse($paginator, Request $request): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'per_page' => (int) $request->get('per_page', 10),
                'sort_field' => $request->get('sort_field', 'created_at'),
                'sort_direction' => $request->get('sort_direction', 'desc'),
            ],
        ];
    }

    /**
     * Transforma una colección aplicando un callback de transformación
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return \Illuminate\Support\Collection
     */
    public function transformCollection($collection, callable $transformer)
    {
        return $collection->map($transformer);
    }
}
