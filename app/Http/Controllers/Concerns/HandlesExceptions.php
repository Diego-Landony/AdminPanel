<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Trait para centralizar el manejo de excepciones en controllers
 * Proporciona métodos consistentes para manejar errores comunes
 */
trait HandlesExceptions
{
    /**
     * Maneja excepciones de base de datos de forma consistente
     *
     * @param  QueryException  $e
     * @param  string  $context  - Contexto de la acción (crear, actualizar, eliminar)
     * @param  string  $entity  - Nombre de la entidad (usuario, cliente, restaurante)
     * @return RedirectResponse
     */
    protected function handleDatabaseException(
        QueryException $e,
        string $context = 'procesar',
        string $entity = 'registro'
    ): RedirectResponse {
        Log::error("Error de base de datos al {$context} {$entity}: ".$e->getMessage(), [
            'exception' => $e,
            'user_id' => auth()->id(),
        ]);

        $message = $e->getMessage();

        // Errores de constraint único (duplicado)
        if (str_contains($message, 'UNIQUE constraint failed') ||
            str_contains($message, 'Duplicate entry') ||
            str_contains($message, 'UNIQUE constraint')) {
            return $this->redirectWithError(
                $this->getUniqueConstraintMessage($context, $entity)
            );
        }

        // Errores de clave foránea
        if (str_contains($message, 'FOREIGN KEY constraint failed') ||
            str_contains($message, 'Cannot delete or update a parent row')) {
            return $this->redirectWithError(
                $this->getForeignKeyMessage($context, $entity)
            );
        }

        // Error genérico de base de datos
        return $this->redirectWithError(
            "Error de base de datos al {$context} el {$entity}. Verifica que los datos sean correctos."
        );
    }

    /**
     * Maneja excepciones de validación
     *
     * @param  ValidationException  $e
     * @return never
     *
     * @throws ValidationException
     */
    protected function handleValidationException(ValidationException $e): never
    {
        // Las excepciones de validación se manejan automáticamente por Laravel
        // Este método existe para consistencia y logging si es necesario
        Log::info('Error de validación', [
            'errors' => $e->errors(),
            'user_id' => auth()->id(),
        ]);

        throw $e;
    }

    /**
     * Maneja excepciones generales no esperadas
     *
     * @param  \Exception  $e
     * @param  string  $context
     * @param  string  $entity
     * @return RedirectResponse
     */
    protected function handleGeneralException(
        \Exception $e,
        string $context = 'procesar',
        string $entity = 'registro'
    ): RedirectResponse {
        Log::error("Error inesperado al {$context} {$entity}: ".$e->getMessage(), [
            'exception' => $e,
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->redirectWithError(
            "Error inesperado al {$context} el {$entity}. Inténtalo de nuevo o contacta al administrador."
        );
    }

    /**
     * Wrapper para ejecutar operaciones con manejo automático de excepciones
     *
     * @param  callable  $operation
     * @param  string  $context
     * @param  string  $entity
     * @return mixed
     */
    protected function executeWithExceptionHandling(
        callable $operation,
        string $context,
        string $entity
    ) {
        try {
            return $operation();
        } catch (ValidationException $e) {
            return $this->handleValidationException($e);
        } catch (QueryException $e) {
            return $this->handleDatabaseException($e, $context, $entity);
        } catch (\Exception $e) {
            return $this->handleGeneralException($e, $context, $entity);
        }
    }

    /**
     * Obtiene el mensaje apropiado para errores de constraint único
     *
     * @param  string  $context
     * @param  string  $entity
     * @return string
     */
    private function getUniqueConstraintMessage(string $context, string $entity): string
    {
        return match ($context) {
            'crear' => "Este {$entity} ya existe en el sistema. Usa datos diferentes.",
            'actualizar' => "Estos datos ya están registrados por otro {$entity}. Usa datos diferentes.",
            default => "Ya existe un {$entity} con estos datos. Usa datos diferentes.",
        };
    }

    /**
     * Obtiene el mensaje apropiado para errores de clave foránea
     *
     * @param  string  $context
     * @param  string  $entity
     * @return string
     */
    private function getForeignKeyMessage(string $context, string $entity): string
    {
        return match ($context) {
            'eliminar' => "No se puede eliminar el {$entity} porque tiene registros asociados.",
            'actualizar' => "No se puede actualizar el {$entity} porque tiene dependencias relacionadas.",
            default => "El {$entity} tiene registros relacionados que impiden esta operación.",
        };
    }

    /**
     * Redirecciona de vuelta con un mensaje de error
     *
     * @param  string  $message
     * @return RedirectResponse
     */
    private function redirectWithError(string $message): RedirectResponse
    {
        return back()->with('error', $message);
    }

    /**
     * Redirecciona de vuelta con un mensaje de éxito
     *
     * @param  string  $message
     * @return RedirectResponse
     */
    protected function redirectWithSuccess(string $message): RedirectResponse
    {
        return back()->with('success', $message);
    }
}
