<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Subir imagen y retornar la URL
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
        ]);

        try {
            $image = $request->file('image');

            // Generar nombre único
            $filename = Str::uuid().'.'.$image->getClientOriginalExtension();

            // Guardar en storage/app/public/images
            $path = $image->storeAs('images', $filename, 'public');

            // Retornar URL pública
            $url = Storage::disk('public')->url($path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar imagen
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $request->input('path');

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

                return response()->json([
                    'success' => true,
                    'message' => 'Imagen eliminada correctamente',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: '.$e->getMessage(),
            ], 500);
        }
    }
}
