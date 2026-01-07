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
     *
     *     @OA\Response(
     *         response=200,
     *         description="Terms and conditions retrieved successfully"
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
     *
     *     @OA\Response(
     *         response=200,
     *         description="Privacy policy retrieved successfully"
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
