<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidPasswordException extends Exception
{
    public function __construct()
    {
        parent::__construct('La contraseña actual es incorrecta');
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => 'INVALID_PASSWORD',
        ], 422);
    }
}
