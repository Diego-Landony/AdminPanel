<?php

namespace App\Exceptions\Order;

use Exception;
use Illuminate\Http\JsonResponse;

class PromotionExpiredException extends Exception
{
    public function __construct(
        public readonly string $promotionName,
        public readonly int $promotionId,
        public readonly ?string $expiredAt = null
    ) {
        $message = sprintf(
            'La promoción "%s" ya no está vigente y no puede ser aplicada a tu pedido.',
            $promotionName
        );
        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => 'PROMOTION_EXPIRED',
            'data' => [
                'promotion_id' => $this->promotionId,
                'promotion_name' => $this->promotionName,
                'expired_at' => $this->expiredAt,
            ],
        ], 422);
    }
}
