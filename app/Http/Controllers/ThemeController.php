<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Controlador para manejar el cambio de tema del sistema
 * Utiliza cookies nativas de Laravel para persistir la preferencia del usuario
 */
class ThemeController extends Controller
{
    /**
     * Actualiza el tema del sistema
     * 
     * @param Request $request - Request con el tema seleccionado
     * @return \Inertia\Response - Respuesta de Inertia
     */
    public function update(Request $request)
    {
        // Validar que el tema sea uno de los permitidos
        $request->validate([
            'theme' => 'required|in:light,dark,system',
        ]);

        $theme = $request->input('theme');
        
        // Establecer cookie con el tema seleccionado
        // La cookie durará 1 año (365 días)
        cookie()->queue('appearance', $theme, 365 * 24 * 60 * 60);

        // Redirigir de vuelta a la página anterior con mensaje de éxito
        return back()->with('theme_updated', 'Tema actualizado correctamente');
    }

    /**
     * Obtiene el tema actual del sistema
     * 
     * @param Request $request - Request actual
     * @return \Inertia\Response - Respuesta de Inertia
     */
    public function get(Request $request)
    {
        $theme = $request->cookie('appearance', 'system');
        
        return Inertia::render('theme-info', [
            'currentTheme' => $theme,
        ]);
    }
}
