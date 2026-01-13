<?php

namespace App\Exceptions\Order;

use Exception;
use Illuminate\Http\JsonResponse;

class MinimumOrderAmountException extends Exception
{
    public function __construct(
        public readonly float $minimumAmount,
        public readonly float $currentAmount,
        public readonly string $restaurantName
    ) {
        $message = sprintf(
            'El monto mínimo de pedido para %s es Q%.2f. Tu pedido actual es Q%.2f.',
            $restaurantName,
            $minimumAmount,
            $currentAmount
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
            'error_code' => 'MINIMUM_ORDER_AMOUNT_NOT_MET',
            'data' => [
                'minimum_amount' => $this->minimumAmount,
                'current_amount' => $this->currentAmount,
                'difference' => round($this->minimumAmount - $this->currentAmount, 2),
                'restaurant_name' => $this->restaurantName,
            ],
        ], 422);
    }
}
