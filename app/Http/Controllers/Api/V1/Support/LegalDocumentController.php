<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\JsonResponse;

class LegalDocumentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/legal/terms",
     *     tags={"Legal"},
     *     summary="Get published terms and conditions",
     *     description="Returns the currently published terms and conditions document.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Terms and conditions retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="version", type="string", example="1.0"),
     *                 @OA\Property(property="content_html", type="string", example="<h1>Términos y Condiciones</h1><p>...</p>"),
     *                 @OA\Property(property="published_at", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00-06:00")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No terms and conditions published",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No hay términos y condiciones publicados."),
     *             @OA\Property(property="data", type="null", nullable=true)
     *         )
     *     )
     * )
     */
    public function terms(): JsonResponse
    {
        $document = LegalDocument::getPublishedTerms();

        if (! $document) {
            return response()->json([
                'message' => 'No hay términos y condiciones publicados.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => [
                'version' => $document->version,
                'content_html' => $document->content_html,
                'published_at' => $document->published_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/legal/privacy",
     *     tags={"Legal"},
     *     summary="Get published privacy policy",
     *     description="Returns the currently published privacy policy document.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Privacy policy retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="version", type="string", example="1.0"),
     *                 @OA\Property(property="content_html", type="string", example="<h1>Política de Privacidad</h1><p>...</p>"),
     *                 @OA\Property(property="published_at", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00-06:00")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No privacy policy published",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No hay política de privacidad publicada."),
     *             @OA\Property(property="data", type="null", nullable=true)
     *         )
     *     )
     * )
     */
    public function privacy(): JsonResponse
    {
        $document = LegalDocument::getPublishedPrivacyPolicy();

        if (! $document) {
            return response()->json([
                'message' => 'No hay política de privacidad publicada.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => [
                'version' => $document->version,
                'content_html' => $document->content_html,
                'published_at' => $document->published_at?->toIso8601String(),
            ],
        ]);
    }
}
