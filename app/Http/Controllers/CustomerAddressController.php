<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    /**
     * Store a newly created address
     */
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'label' => 'nullable|string|max:255',
            'address_line' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'delivery_notes' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $address = $customer->addresses()->create([
            'label' => $request->label,
            'address_line' => $request->address_line,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'delivery_notes' => $request->delivery_notes,
            'is_default' => $request->boolean('is_default'),
        ]);

        if ($request->boolean('is_default')) {
            $address->markAsDefault();
        }

        return back()->with('success', 'Dirección agregada exitosamente');
    }

    /**
     * Update the specified address
     */
    public function update(Request $request, Customer $customer, CustomerAddress $address): RedirectResponse
    {
        if ($address->customer_id !== $customer->id) {
            return back()->with('error', 'Dirección no encontrada');
        }

        $request->validate([
            'label' => 'nullable|string|max:255',
            'address_line' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'delivery_notes' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $address->update([
            'label' => $request->label,
            'address_line' => $request->address_line,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'delivery_notes' => $request->delivery_notes,
            'is_default' => $request->boolean('is_default'),
        ]);

        if ($request->boolean('is_default')) {
            $address->markAsDefault();
        }

        return back()->with('success', 'Dirección actualizada exitosamente');
    }

    /**
     * Remove the specified address
     */
    public function destroy(Customer $customer, CustomerAddress $address): RedirectResponse
    {
        if ($address->customer_id !== $customer->id) {
            return back()->with('error', 'Dirección no encontrada');
        }

        $address->delete();

        return back()->with('success', 'Dirección eliminada exitosamente');
    }
}
