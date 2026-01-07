<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerNit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerNitController extends Controller
{
    /**
     * Store a newly created NIT
     */
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'nit' => 'required|string|max:20',
            'nit_type' => 'required|in:personal,company,other',
            'nit_name' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        // Verificar que el NIT no esté duplicado para este cliente
        $exists = $customer->nits()->where('nit', $request->nit)->exists();
        if ($exists) {
            return back()->with('error', 'Este NIT ya está registrado para este cliente');
        }

        $nit = $customer->nits()->create([
            'nit' => $request->nit,
            'nit_type' => $request->nit_type,
            'nit_name' => $request->nit_name,
            'is_default' => false,
        ]);

        if ($request->boolean('is_default')) {
            $nit->markAsDefault();
        }

        return back()->with('success', 'NIT agregado exitosamente');
    }

    /**
     * Update the specified NIT
     */
    public function update(Request $request, Customer $customer, CustomerNit $nit): RedirectResponse
    {
        if ($nit->customer_id !== $customer->id) {
            return back()->with('error', 'NIT no encontrado');
        }

        $request->validate([
            'nit' => 'required|string|max:20',
            'nit_type' => 'required|in:personal,company,other',
            'nit_name' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        // Verificar que el NIT no esté duplicado (excepto el actual)
        $exists = $customer->nits()
            ->where('nit', $request->nit)
            ->where('id', '!=', $nit->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Este NIT ya está registrado para este cliente');
        }

        $nit->update([
            'nit' => $request->nit,
            'nit_type' => $request->nit_type,
            'nit_name' => $request->nit_name,
        ]);

        if ($request->boolean('is_default')) {
            $nit->markAsDefault();
        }

        return back()->with('success', 'NIT actualizado exitosamente');
    }

    /**
     * Remove the specified NIT
     */
    public function destroy(Customer $customer, CustomerNit $nit): RedirectResponse
    {
        if ($nit->customer_id !== $customer->id) {
            return back()->with('error', 'NIT no encontrado');
        }

        $nitNumber = $nit->nit;
        $nit->delete();

        return back()->with('success', "NIT {$nitNumber} eliminado exitosamente");
    }
}
