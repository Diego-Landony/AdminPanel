<?php

namespace Database\Seeders;

use App\Models\SupportReason;
use Illuminate\Database\Seeder;

class SupportReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name' => 'Problema con mi pedido', 'slug' => 'order_issue', 'sort_order' => 1],
            ['name' => 'Problema con pago', 'slug' => 'payment', 'sort_order' => 2],
            ['name' => 'Mi cuenta', 'slug' => 'account', 'sort_order' => 3],
            ['name' => 'Sugerencia', 'slug' => 'suggestion', 'sort_order' => 4],
            ['name' => 'Otro', 'slug' => 'other', 'sort_order' => 5],
        ];

        foreach ($reasons as $reason) {
            SupportReason::updateOrCreate(
                ['slug' => $reason['slug']],
                $reason
            );
        }
    }
}
