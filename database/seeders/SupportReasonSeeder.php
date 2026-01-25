<?php

namespace Database\Seeders;

use App\Models\SupportReason;
use Illuminate\Database\Seeder;

class SupportReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name' => 'Tengo una idea/recomendación', 'slug' => 'suggestion', 'sort_order' => 1],
            ['name' => 'Problema con mi pedido', 'slug' => 'order_issue', 'sort_order' => 2],
            ['name' => 'Problema con el pago', 'slug' => 'payment', 'sort_order' => 3],
            ['name' => 'Problemas con mis puntos', 'slug' => 'points_issue', 'sort_order' => 4],
            ['name' => 'Dudas con una promoción', 'slug' => 'promotion_question', 'sort_order' => 5],
            ['name' => 'Problemas en un restaurante', 'slug' => 'restaurant_issue', 'sort_order' => 6],
            ['name' => 'Problemas con mi cuenta', 'slug' => 'account', 'sort_order' => 7],
            ['name' => 'Otro', 'slug' => 'other', 'sort_order' => 8],
        ];

        foreach ($reasons as $reason) {
            SupportReason::updateOrCreate(
                ['slug' => $reason['slug']],
                $reason
            );
        }
    }
}
