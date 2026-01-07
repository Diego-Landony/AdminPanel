<?php

namespace App\Http\Controllers;

use App\Models\PointsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PointsSettingController extends Controller
{
    public function index(): Response
    {
        $settings = PointsSetting::getOrCreate();

        return Inertia::render('settings/points', [
            'settings' => [
                'quetzales_per_point' => $settings->quetzales_per_point,
                'expiration_method' => $settings->expiration_method,
                'expiration_months' => $settings->expiration_months,
                'rounding_threshold' => $settings->rounding_threshold,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quetzales_per_point' => ['required', 'integer', 'min:1', 'max:1000'],
            'expiration_method' => ['required', 'string', 'in:total,fifo'],
            'expiration_months' => ['required', 'integer', 'min:1', 'max:24'],
            'rounding_threshold' => ['required', 'numeric', 'min:0', 'max:0.99'],
        ], [
            'quetzales_per_point.required' => 'Los quetzales por punto son obligatorios.',
            'quetzales_per_point.min' => 'Los quetzales por punto deben ser al menos 1.',
            'expiration_method.required' => 'El metodo de expiracion es obligatorio.',
            'expiration_method.in' => 'El metodo de expiracion debe ser "total" o "fifo".',
            'expiration_months.required' => 'Los meses de expiracion son obligatorios.',
            'expiration_months.min' => 'Los meses de expiracion deben ser al menos 1.',
        ]);

        $settings = PointsSetting::getOrCreate();
        $settings->update($validated);

        return back()->with('success', 'Configuracion de puntos actualizada correctamente.');
    }
}
