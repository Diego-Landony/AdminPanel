<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

/**
 * Controlador para manejar la persistencia del tema en el servidor.
 */
class ThemeController extends Controller
{
    /**
     * Actualiza la cookie del tema del sistema.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);

        $theme = $validated['theme'];

        // Almacenar la preferencia en una cookie por un año.
        Cookie::queue('appearance', $theme, 60 * 24 * 365);

        return response()->json(['message' => 'Theme updated successfully.']);
    }
}
