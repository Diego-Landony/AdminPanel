<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerDeviceController extends Controller
{
    /**
     * Store a newly created device
     */
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|max:255|unique:customer_devices',
            'device_type' => 'nullable|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
        ]);

        $customer->devices()->create([
            'fcm_token' => $request->fcm_token,
            'device_type' => $request->device_type,
            'device_name' => $request->device_name,
            'device_model' => $request->device_model,
            'last_used_at' => now(),
        ]);

        return back()->with('success', 'Dispositivo registrado exitosamente');
    }

    /**
     * Update the specified device
     */
    public function update(Request $request, Customer $customer, CustomerDevice $device): RedirectResponse
    {
        if ($device->customer_id !== $customer->id) {
            return back()->with('error', 'Dispositivo no encontrado');
        }

        $request->validate([
            'fcm_token' => 'required|string|max:255|unique:customer_devices,fcm_token,'.$device->id,
            'device_type' => 'nullable|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
        ]);

        $device->update([
            'fcm_token' => $request->fcm_token,
            'device_type' => $request->device_type,
            'device_name' => $request->device_name,
            'device_model' => $request->device_model,
        ]);

        return back()->with('success', 'Dispositivo actualizado exitosamente');
    }

    /**
     * Remove the specified device
     */
    public function destroy(Customer $customer, CustomerDevice $device): RedirectResponse
    {
        if ($device->customer_id !== $customer->id) {
            return back()->with('error', 'Dispositivo no encontrado');
        }

        $deviceName = $device->device_name ?? 'Dispositivo';
        $device->delete();

        return back()->with('success', "{$deviceName} desvinculado exitosamente");
    }

    /**
     * Remove all inactive devices for a customer
     * Los dispositivos inactivos son aquellos marcados como is_active = false
     */
    public function destroyInactive(Customer $customer): RedirectResponse
    {
        $count = $customer->devices()->inactive()->count();

        if ($count === 0) {
            return back()->with('info', 'No hay dispositivos inactivos para eliminar');
        }

        $customer->devices()->inactive()->delete();

        return back()->with('success', "{$count} dispositivo(s) inactivo(s) eliminado(s) exitosamente");
    }
}
