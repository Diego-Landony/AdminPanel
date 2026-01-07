<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityFeedService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de actividad del sistema.
 */
class ActivityController extends Controller
{
    public function __construct(
        protected ActivityFeedService $feedService
    ) {}

    /**
     * Muestra la página principal de actividad con logs y actividades.
     */
    public function index(Request $request): Response
    {
        $filters = $this->extractFilters($request);
        $perPage = (int) $request->get('per_page', 15);

        // Obtener feed paginado eficientemente
        $activities = $this->feedService->getFeed($filters, $perPage);
        $activities->appends($request->all());

        // Obtener estadísticas (cacheadas)
        $stats = $this->feedService->getStats([
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]);

        // Obtener opciones para filtros
        $options = $this->getFilterOptions();

        return Inertia::render('activity/index', [
            'activities' => $activities,
            'filters' => $filters,
            'stats' => $stats,
            'options' => $options,
        ]);
    }

    /**
     * Handle POST requests for search/filter.
     */
    public function store(Request $request): Response
    {
        return $this->index($request);
    }

    /**
     * Extract filters from request.
     */
    protected function extractFilters(Request $request): array
    {
        return [
            'search' => $request->get('search', ''),
            'event_type' => $request->get('event_type', ''),
            'user_id' => $request->get('user_id', ''),
            'start_date' => $request->get('start_date', ''),
            'end_date' => $request->get('end_date', ''),
            'per_page' => (int) $request->get('per_page', 15),
        ];
    }

    /**
     * Get options for filter dropdowns.
     */
    protected function getFilterOptions(): array
    {
        return [
            'event_types' => config('activity.event_types', []),
            'users' => User::select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'per_page_options' => [15, 25, 50, 100],
        ];
    }
}
