<?php

namespace App\Http\Controllers;

use App\Http\Requests\RestaurantUser\StoreRestaurantUserRequest;
use App\Http\Requests\RestaurantUser\UpdateRestaurantUserRequest;
use App\Models\Restaurant;
use App\Models\RestaurantUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantUserController extends Controller
{
    /**
     * Muestra la lista de usuarios de un restaurante.
     */
    public function index(Request $request, Restaurant $restaurant): Response
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 15);
        $sortField = $request->get('sort_field', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        $query = RestaurantUser::query()
            ->forRestaurant($restaurant->id)
            ->select([
                'id',
                'restaurant_id',
                'name',
                'email',
                'is_active',
                'last_activity_at',
                'email_verified_at',
                'created_at',
                'updated_at',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortField, $sortDirection);

        $users = $query->paginate($perPage)
            ->appends($request->all())
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'restaurant_id' => $user->restaurant_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'is_online' => $user->is_online,
                    'status' => $user->status,
                    'email_verified_at' => $user->email_verified_at,
                    'last_activity_at' => $user->last_activity_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

        // Estadisticas
        $totalStats = RestaurantUser::forRestaurant($restaurant->id)
            ->select(['id', 'is_active'])
            ->get();

        return Inertia::render('restaurants/users/index', [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
            ],
            'users' => $users,
            'total_users' => $totalStats->count(),
            'active_users' => $totalStats->where('is_active', true)->count(),
            'filters' => [
                'search' => $search,
                'per_page' => (int) $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Almacena un nuevo usuario de restaurante.
     */
    public function store(StoreRestaurantUserRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $data = $request->validated();
        $data['restaurant_id'] = $restaurant->id;
        $data['password'] = Hash::make($data['password']);

        RestaurantUser::create($data);

        return back()->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Actualiza un usuario de restaurante.
     */
    public function update(
        UpdateRestaurantUserRequest $request,
        Restaurant $restaurant,
        RestaurantUser $restaurantUser
    ): RedirectResponse {
        // Verificar que el usuario pertenece al restaurante
        if ($restaurantUser->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        $data = $request->validated();

        if (isset($data['password']) && $data['password'] !== null) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $restaurantUser->update($data);

        return back()->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Elimina un usuario de restaurante.
     */
    public function destroy(Restaurant $restaurant, RestaurantUser $restaurantUser): RedirectResponse
    {
        // Verificar que el usuario pertenece al restaurante
        if ($restaurantUser->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        $userName = $restaurantUser->name;
        $restaurantUser->delete();

        return back()->with('success', "Usuario '{$userName}' eliminado exitosamente.");
    }

    /**
     * Restablece la contrasena de un usuario de restaurante.
     */
    public function resetPassword(Restaurant $restaurant, RestaurantUser $restaurantUser): RedirectResponse
    {
        // Verificar que el usuario pertenece al restaurante
        if ($restaurantUser->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        // Generar una contrasena temporal
        $temporaryPassword = Str::random(12);

        $restaurantUser->update([
            'password' => Hash::make($temporaryPassword),
        ]);

        // En produccion, aqui se enviaria un email con la contrasena temporal
        // Por ahora retornamos la contrasena en el mensaje de exito

        return back()->with('success', "Contrasena restablecida. Nueva contrasena temporal: {$temporaryPassword}");
    }
}
