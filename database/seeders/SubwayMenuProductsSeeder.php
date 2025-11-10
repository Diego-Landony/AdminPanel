<?php

namespace Database\Seeders;

use App\Models\Menu\Category;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubwayMenuProductsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🥪 Creando productos del menú de Subway...');

        // Limpiar datos existentes (respetando foreign keys)
        DB::table('bundle_promotion_item_options')->delete();
        DB::table('bundle_promotion_items')->delete();
        DB::table('promotion_items')->delete();
        DB::table('combo_item_options')->delete();
        ComboItem::query()->delete();
        DB::table('product_sections')->delete();
        DB::table('category_product')->delete();
        ProductVariant::query()->delete();
        Product::query()->delete();

        $this->createSubs();
        $this->createSubwaySeries();
        $this->createWraps();
        $this->createBebidas();
        $this->createEnsaladas();
        $this->createPizzasPersonales();
        $this->createComplementos();
        $this->createPostres();
        $this->createDesayunos();

        $this->command->info('   ✅ Productos creados exitosamente');
    }

    private function createSubs(): void
    {
        $this->command->line('   🥖 Creando Subs...');

        $category = Category::where('name', 'Subs')->first();
        $sections = Section::whereIn('title', [
            'Tipo de Pan',
            'Queso',
            'Vegetales',
            'Salsas',
            'Preparación',
            'Extras',
        ])->pluck('id');

        $subs = [
            [
                'name' => 'Italian B.M.T.',
                'description' => 'Pepperoni, salami y jamón con tu elección de vegetales frescos, queso y aderezos',
                'prices_15cm' => ['pickup_capital' => 35, 'domicilio_capital' => 40, 'pickup_interior' => 37, 'domicilio_interior' => 42],
                'prices_30cm' => ['pickup_capital' => 65, 'domicilio_capital' => 70, 'pickup_interior' => 67, 'domicilio_interior' => 72],
            ],
            [
                'name' => 'Subway Club',
                'description' => 'Pavo, jamón y roast beef con vegetales frescos',
                'prices_15cm' => ['pickup_capital' => 38, 'domicilio_capital' => 43, 'pickup_interior' => 40, 'domicilio_interior' => 45],
                'prices_30cm' => ['pickup_capital' => 68, 'domicilio_capital' => 73, 'pickup_interior' => 70, 'domicilio_interior' => 75],
            ],
            [
                'name' => 'Pollo Teriyaki',
                'description' => 'Tiras de pollo marinadas en salsa teriyaki con vegetales',
                'prices_15cm' => ['pickup_capital' => 36, 'domicilio_capital' => 41, 'pickup_interior' => 38, 'domicilio_interior' => 43],
                'prices_30cm' => ['pickup_capital' => 66, 'domicilio_capital' => 71, 'pickup_interior' => 68, 'domicilio_interior' => 73],
            ],
            [
                'name' => 'Pechuga de Pavo',
                'description' => 'Pechuga de pavo en rebanadas con vegetales frescos',
                'prices_15cm' => ['pickup_capital' => 34, 'domicilio_capital' => 39, 'pickup_interior' => 36, 'domicilio_interior' => 41],
                'prices_30cm' => ['pickup_capital' => 64, 'domicilio_capital' => 69, 'pickup_interior' => 66, 'domicilio_interior' => 71],
            ],
            [
                'name' => 'Atún',
                'description' => 'Atún mezclado con mayonesa y vegetales frescos',
                'prices_15cm' => ['pickup_capital' => 33, 'domicilio_capital' => 38, 'pickup_interior' => 35, 'domicilio_interior' => 40],
                'prices_30cm' => ['pickup_capital' => 63, 'domicilio_capital' => 68, 'pickup_interior' => 65, 'domicilio_interior' => 70],
            ],
            [
                'name' => 'Steak & Cheese',
                'description' => 'Carne de res marinada con queso derretido',
                'prices_15cm' => ['pickup_capital' => 42, 'domicilio_capital' => 47, 'pickup_interior' => 44, 'domicilio_interior' => 49],
                'prices_30cm' => ['pickup_capital' => 75, 'domicilio_capital' => 80, 'pickup_interior' => 77, 'domicilio_interior' => 82],
            ],
            [
                'name' => 'Pollo BBQ',
                'description' => 'Pollo con salsa BBQ y vegetales',
                'prices_15cm' => ['pickup_capital' => 37, 'domicilio_capital' => 42, 'pickup_interior' => 39, 'domicilio_interior' => 44],
                'prices_30cm' => ['pickup_capital' => 67, 'domicilio_capital' => 72, 'pickup_interior' => 69, 'domicilio_interior' => 74],
            ],
            [
                'name' => 'Subway Melt',
                'description' => 'Pavo, jamón, tocino y queso derretido',
                'prices_15cm' => ['pickup_capital' => 40, 'domicilio_capital' => 45, 'pickup_interior' => 42, 'domicilio_interior' => 47],
                'prices_30cm' => ['pickup_capital' => 72, 'domicilio_capital' => 77, 'pickup_interior' => 74, 'domicilio_interior' => 79],
            ],
            [
                'name' => 'Veggie Delite',
                'description' => 'Solo vegetales frescos y tus aderezos favoritos',
                'prices_15cm' => ['pickup_capital' => 25, 'domicilio_capital' => 30, 'pickup_interior' => 27, 'domicilio_interior' => 32],
                'prices_30cm' => ['pickup_capital' => 48, 'domicilio_capital' => 53, 'pickup_interior' => 50, 'domicilio_interior' => 55],
            ],
            [
                'name' => 'Jamón',
                'description' => 'Jamón de calidad premium con vegetales frescos',
                'prices_15cm' => ['pickup_capital' => 32, 'domicilio_capital' => 37, 'pickup_interior' => 34, 'domicilio_interior' => 39],
                'prices_30cm' => ['pickup_capital' => 62, 'domicilio_capital' => 67, 'pickup_interior' => 64, 'domicilio_interior' => 69],
            ],
            [
                'name' => 'Pechuga de Pollo',
                'description' => 'Pechuga de pollo jugosa con vegetales frescos',
                'prices_15cm' => ['pickup_capital' => 35, 'domicilio_capital' => 40, 'pickup_interior' => 37, 'domicilio_interior' => 42],
                'prices_30cm' => ['pickup_capital' => 65, 'domicilio_capital' => 70, 'pickup_interior' => 67, 'domicilio_interior' => 72],
            ],
        ];

        foreach ($subs as $subData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $subData['name'],
                'description' => $subData['description'],
                'image' => null,
                'has_variants' => true,
                'precio_pickup_capital' => 0,
                'precio_domicilio_capital' => 0,
                'precio_pickup_interior' => 0,
                'precio_domicilio_interior' => 0,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            // Crear variante 15cm
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => Str::slug($subData['name']).'-15cm',
                'name' => '15cm',
                'size' => '15cm',
                'precio_pickup_capital' => $subData['prices_15cm']['pickup_capital'],
                'precio_domicilio_capital' => $subData['prices_15cm']['domicilio_capital'],
                'precio_pickup_interior' => $subData['prices_15cm']['pickup_interior'],
                'precio_domicilio_interior' => $subData['prices_15cm']['domicilio_interior'],
                'is_active' => true,
                'sort_order' => 1,
            ]);

            // Crear variante 30cm
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => Str::slug($subData['name']).'-30cm',
                'name' => '30cm',
                'size' => '30cm',
                'precio_pickup_capital' => $subData['prices_30cm']['pickup_capital'],
                'precio_domicilio_capital' => $subData['prices_30cm']['domicilio_capital'],
                'precio_pickup_interior' => $subData['prices_30cm']['pickup_interior'],
                'precio_domicilio_interior' => $subData['prices_30cm']['domicilio_interior'],
                'is_active' => true,
                'sort_order' => 2,
            ]);

            // Asociar secciones
            $product->sections()->attach($sections);

            $this->command->line("      ✓ {$product->name} (2 variantes)");
        }
    }

    private function createSubwaySeries(): void
    {
        $this->command->line('   ⭐ Creando Subway Series...');

        $category = Category::where('name', 'Subway Series')->first();

        $series = [
            ['name' => 'DON B.M.T.', 'description' => 'Pepperoni, salami y jamón de la Serie Subway', 'precio_pickup_capital' => 45, 'precio_domicilio_capital' => 50, 'precio_pickup_interior' => 47, 'precio_domicilio_interior' => 52],
            ['name' => 'CAPITÁN YAKI', 'description' => 'Pollo Teriyaki especial de la Serie Subway', 'precio_pickup_capital' => 46, 'precio_domicilio_capital' => 51, 'precio_pickup_interior' => 48, 'precio_domicilio_interior' => 53],
            ['name' => 'EL JEFE', 'description' => 'Carne premium especial de la Serie Subway', 'precio_pickup_capital' => 48, 'precio_domicilio_capital' => 53, 'precio_pickup_interior' => 50, 'precio_domicilio_interior' => 55],
        ];

        foreach ($series as $seriesData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $seriesData['name'],
                'description' => $seriesData['description'],
                'image' => null,
                'has_variants' => false,
                'precio_pickup_capital' => $seriesData['precio_pickup_capital'],
                'precio_domicilio_capital' => $seriesData['precio_domicilio_capital'],
                'precio_pickup_interior' => $seriesData['precio_pickup_interior'],
                'precio_domicilio_interior' => $seriesData['precio_domicilio_interior'],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $this->command->line("      ✓ {$product->name}");
        }
    }

    private function createWraps(): void
    {
        $this->command->line('   🌯 Creando Wraps...');

        $category = Category::where('name', 'Wraps')->first();
        $sections = Section::whereIn('title', [
            'Queso',
            'Vegetales',
            'Salsas',
            'Preparación',
        ])->pluck('id');

        $wraps = [
            [
                'name' => 'Wrap de Pollo Teriyaki',
                'description' => 'Tiras de pollo marinadas en salsa teriyaki envueltas en tortilla de harina con vegetales frescos',
                'precio_pickup_capital' => 42,
                'precio_domicilio_capital' => 47,
                'precio_pickup_interior' => 44,
                'precio_domicilio_interior' => 49,
            ],
            [
                'name' => 'Wrap Italian B.M.T.',
                'description' => 'Pepperoni, salami y jamón con vegetales frescos en tortilla de harina',
                'precio_pickup_capital' => 40,
                'precio_domicilio_capital' => 45,
                'precio_pickup_interior' => 42,
                'precio_domicilio_interior' => 47,
            ],
            [
                'name' => 'Wrap de Pavo',
                'description' => 'Pechuga de pavo en rebanadas con vegetales frescos en tortilla de harina',
                'precio_pickup_capital' => 38,
                'precio_domicilio_capital' => 43,
                'precio_pickup_interior' => 40,
                'precio_domicilio_interior' => 45,
            ],
            [
                'name' => 'Wrap Veggie',
                'description' => 'Vegetales frescos variados envueltos en tortilla de harina',
                'precio_pickup_capital' => 32,
                'precio_domicilio_capital' => 37,
                'precio_pickup_interior' => 34,
                'precio_domicilio_interior' => 39,
            ],
        ];

        foreach ($wraps as $wrapData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $wrapData['name'],
                'description' => $wrapData['description'],
                'image' => null,
                'has_variants' => false,
                'precio_pickup_capital' => $wrapData['precio_pickup_capital'],
                'precio_domicilio_capital' => $wrapData['precio_domicilio_capital'],
                'precio_pickup_interior' => $wrapData['precio_pickup_interior'],
                'precio_domicilio_interior' => $wrapData['precio_domicilio_interior'],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            // Asociar secciones
            $product->sections()->attach($sections);

            $this->command->line("      ✓ {$product->name}");
        }
    }

    private function createBebidas(): void
    {
        $this->command->line('   🥤 Creando Bebidas...');

        $category = Category::where('name', 'Bebidas')->first();

        $bebidas = [
            ['name' => 'Jugo Petit', 'description' => 'Jugo Petit en presentación individual', 'precio_pickup_capital' => 13, 'precio_domicilio_capital' => 15, 'precio_pickup_interior' => 14, 'precio_domicilio_interior' => 16],
            ['name' => 'Agua Pura', 'description' => 'Botella de agua purificada', 'precio_pickup_capital' => 10, 'precio_domicilio_capital' => 12, 'precio_pickup_interior' => 11, 'precio_domicilio_interior' => 13],
            ['name' => 'Gaseosa Lata', 'description' => 'Gaseosa en lata', 'precio_pickup_capital' => 10, 'precio_domicilio_capital' => 12, 'precio_pickup_interior' => 11, 'precio_domicilio_interior' => 13],
            ['name' => 'Be Light', 'description' => 'Be Light sabor original', 'precio_pickup_capital' => 15, 'precio_domicilio_capital' => 17, 'precio_pickup_interior' => 16, 'precio_domicilio_interior' => 18],
            ['name' => 'Té Lipton', 'description' => 'Té frío Lipton', 'precio_pickup_capital' => 15, 'precio_domicilio_capital' => 17, 'precio_pickup_interior' => 16, 'precio_domicilio_interior' => 18],
            ['name' => 'Grapette', 'description' => 'Gaseosa Grapette sabor uva', 'precio_pickup_capital' => 10, 'precio_domicilio_capital' => 12, 'precio_pickup_interior' => 11, 'precio_domicilio_interior' => 13],
            ['name' => 'Seven up', 'description' => 'Gaseosa Seven up limón-lima', 'precio_pickup_capital' => 10, 'precio_domicilio_capital' => 12, 'precio_pickup_interior' => 11, 'precio_domicilio_interior' => 13],
            ['name' => 'Pepsi lata', 'description' => 'Pepsi en lata', 'precio_pickup_capital' => 10, 'precio_domicilio_capital' => 12, 'precio_pickup_interior' => 11, 'precio_domicilio_interior' => 13],
            ['name' => 'Be light manzana', 'description' => 'Be Light sabor manzana', 'precio_pickup_capital' => 15, 'precio_domicilio_capital' => 17, 'precio_pickup_interior' => 16, 'precio_domicilio_interior' => 18],
            ['name' => 'Be light jamaica', 'description' => 'Be Light sabor jamaica', 'precio_pickup_capital' => 15, 'precio_domicilio_capital' => 17, 'precio_pickup_interior' => 16, 'precio_domicilio_interior' => 18],
            ['name' => 'Be light limón', 'description' => 'Be Light sabor limón', 'precio_pickup_capital' => 15, 'precio_domicilio_capital' => 17, 'precio_pickup_interior' => 16, 'precio_domicilio_interior' => 18],
        ];

        foreach ($bebidas as $bebidaData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $bebidaData['name'],
                'description' => $bebidaData['description'],
                'image' => null,
                'has_variants' => false,
                'precio_pickup_capital' => $bebidaData['precio_pickup_capital'],
                'precio_domicilio_capital' => $bebidaData['precio_domicilio_capital'],
                'precio_pickup_interior' => $bebidaData['precio_pickup_interior'],
                'precio_domicilio_interior' => $bebidaData['precio_domicilio_interior'],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $this->command->line("      ✓ {$product->name}");
        }
    }

    private function createEnsaladas(): void
    {
        $this->command->line('   🥗 Creando Ensaladas...');

        $category = Category::where('name', 'Ensaladas')->first();

        $ensaladas = [
            [
                'name' => 'Ensalada B.M.T',
                'description' => 'Pepperoni, salami y jamón sobre lechuga fresca con vegetales',
                'precio_pickup_capital' => 40,
                'precio_domicilio_capital' => 45,
                'precio_pickup_interior' => 42,
                'precio_domicilio_interior' => 47,
            ],
            [
                'name' => 'Ensalada de Pollo Teriyaki',
                'description' => 'Tiras de pollo teriyaki sobre lechuga con vegetales frescos',
                'precio_pickup_capital' => 40,
                'precio_domicilio_capital' => 45,
                'precio_pickup_interior' => 42,
                'precio_domicilio_interior' => 47,
            ],
            [
                'name' => 'Ensalada Veggie',
                'description' => 'Vegetales frescos variados con tu aderezo favorito',
                'precio_pickup_capital' => 32,
                'precio_domicilio_capital' => 37,
                'precio_pickup_interior' => 34,
                'precio_domicilio_interior' => 39,
            ],
        ];

        foreach ($ensaladas as $ensaladaData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $ensaladaData['name'],
                'description' => $ensaladaData['description'],
                'image' => null,
                'has_variants' => false,
                'precio_pickup_capital' => $ensaladaData['precio_pickup_capital'],
                'precio_domicilio_capital' => $ensaladaData['precio_domicilio_capital'],
                'precio_pickup_interior' => $ensaladaData['precio_pickup_interior'],
                'precio_domicilio_interior' => $ensaladaData['precio_domicilio_interior'],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $this->command->line("      ✓ {$product->name}");
        }
    }

    private function createPizzasPersonales(): void
    {
        $this->command->line('   🍕 Creando Pizzas Personales...');

        $category = Category::where('name', 'Pizzas Personales')->first();

        $pizzas = [
            [
                'name' => 'Pizza Personal Pepperoni',
                'description' => 'Pizza personal con pepperoni y queso mozzarella',
                'precio_pickup_capital' => 38,
                'precio_domicilio_capital' => 43,
                'precio_pickup_interior' => 40,
                'precio_domicilio_interior' => 45,
            ],
            [
                'name' => 'Pizza Personal Vegetariana',
                'description' => 'Pizza personal con vegetales frescos y queso mozzarella',
                'precio_pickup_capital' => 35,
                'precio_domicilio_capital' => 40,
                'precio_pickup_interior' => 37,
                'precio_domicilio_interior' => 42,
            ],
            [
                'name' => 'Pizza Personal Jamón y Queso',
                'description' => 'Pizza personal con jamón y queso mozzarella',
                'precio_pickup_capital' => 36,
                'precio_domicilio_capital' => 41,
                'precio_pickup_interior' => 38,
                'precio_domicilio_interior' => 43,
            ],
            [
                'name' => 'Pizza Personal Suprema',
                'description' => 'Pizza personal con pepperoni, salami, jamón, vegetales y queso',
                'precio_pickup_capital' => 42,
                'precio_domicilio_capital' => 47,
                'precio_pickup_interior' => 44,
                'precio_domicilio_interior' => 49,
            ],
        ];

        foreach ($pizzas as $pizzaData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $pizzaData['name'],
                'description' => $pizzaData['description'],
                'image' => null,
                'has_variants' => false,
                'precio_pickup_capital' => $pizzaData['precio_pickup_capital'],
                'precio_domicilio_capital' => $pizzaData['precio_domicilio_capital'],
                'precio_pickup_interior' => $pizzaData['precio_pickup_interior'],
                'precio_domicilio_interior' => $pizzaData['precio_domicilio_interior'],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $this->command->line("      ✓ {$product->name}");
        }
    }

    private function createComplementos(): void
    {
        $this->command->line('   🍪 Creando Complementos...');

        $category = Category::where('name', 'Complementos')->first();

        $complementos = [
            [
                'name' => 'Papas Lays',
                'description' => 'Bolsa de papas fritas Lays',
                'precio_pickup_capital' => 8,
                'precio_domicilio_capital' => 10,
                'precio_pickup_interior' => 9,
                'precio_domicilio_interior' => 11,
            ],
            [
                'name' => 'Doritos',
                'description' => 'Bolsa de Doritos sabor original',
                'precio_pickup_capital' => 9,
                'precio_domicilio_capital' => 11,
                'precio_pickup_interior' => 10,
                'precio_domicilio_interior' => 12,
            ],
            [
                'name' => 'Sopa del Día',
                'description' => 'Sopa especial del día caliente',
                'precio_pickup_capital' => 15,
                'precio_domicilio_capital' => 18,
                'precio_pickup_interior' => 16,
                'precio_domicilio_interior' => 19,
            ],
        ];

        foreach ($complementos as $complementoData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $complementoData['name'],
                'description' => $complementoData['description'],
                'image' => null,
                'has_variants' => false,
                'precio_pickup_capital' => $complementoData['precio_pickup_capital'],
                'precio_domicilio_capital' => $complementoData['precio_domicilio_capital'],
                'precio_pickup_interior' => $complementoData['precio_pickup_interior'],
                'precio_domicilio_interior' => $complementoData['precio_domicilio_interior'],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $this->command->line("      ✓ {$product->name}");
        }
    }

    private function createPostres(): void
    {
        $this->command->line('   🍰 Creando Postres...');

        $category = Category::where('name', 'Postres')->first();

        $postres = [
            [
                'name' => 'galleta con chocolate',
                'description' => 'Galleta grande con chispas de chocolate',
                'precio_pickup_capital' => 12,
                'precio_domicilio_capital' => 14,
                'precio_pickup_interior' => 13,
                'precio_domicilio_interior' => 15,
            ],
            [
                'name' => 'Cookie de Avena con Pasas',
                'description' => 'Galleta de avena con pasas',
                'precio_pickup_capital' => 12,
                'precio_domicilio_capital' => 14,
                'precio_pickup_interior' => 13,
                'precio_domicilio_interior' => 15,
            ],
            [
                'name' => 'Muffin de Arándanos',
                'description' => 'Muffin fresco de arándanos',
                'precio_pickup_capital' => 15,
                'precio_domicilio_capital' => 17,
                'precio_pickup_interior' => 16,
                'precio_domicilio_interior' => 18,
            ],
        ];

        foreach ($postres as $postreData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $postreData['name'],
                'description' => $postreData['description'],
                'image' => null,
                'has_variants' => false,
                'precio_pickup_capital' => $postreData['precio_pickup_capital'],
                'precio_domicilio_capital' => $postreData['precio_domicilio_capital'],
                'precio_pickup_interior' => $postreData['precio_pickup_interior'],
                'precio_domicilio_interior' => $postreData['precio_domicilio_interior'],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $this->command->line("      ✓ {$product->name}");
        }
    }

    private function createDesayunos(): void
    {
        $this->command->line('   🍳 Creando Desayunos...');

        $category = Category::where('name', 'Desayunos')->first();
        $sections = Section::whereIn('title', [
            'Tipo de Pan',
            'Queso',
            'Vegetales',
            'Salsas',
            'Preparación',
        ])->pluck('id');

        $desayunos = [
            [
                'name' => 'Desayuno con Tocino y Huevo',
                'description' => 'Tocino crujiente y huevo con queso en tu pan favorito',
                'prices_15cm' => ['pickup_capital' => 28, 'domicilio_capital' => 33, 'pickup_interior' => 30, 'domicilio_interior' => 35],
                'prices_30cm' => ['pickup_capital' => 52, 'domicilio_capital' => 57, 'pickup_interior' => 54, 'domicilio_interior' => 59],
            ],
            [
                'name' => 'Desayuno con Jamón y Huevo',
                'description' => 'Jamón y huevo revuelto con queso',
                'prices_15cm' => ['pickup_capital' => 26, 'domicilio_capital' => 31, 'pickup_interior' => 28, 'domicilio_interior' => 33],
                'prices_30cm' => ['pickup_capital' => 50, 'domicilio_capital' => 55, 'pickup_interior' => 52, 'domicilio_interior' => 57],
            ],
            [
                'name' => 'Desayuno Steak y Huevo',
                'description' => 'Carne de res y huevo con queso derretido',
                'prices_15cm' => ['pickup_capital' => 35, 'domicilio_capital' => 40, 'pickup_interior' => 37, 'domicilio_interior' => 42],
                'prices_30cm' => ['pickup_capital' => 62, 'domicilio_capital' => 67, 'pickup_interior' => 64, 'domicilio_interior' => 69],
            ],
            [
                'name' => 'Desayuno chilero way',
                'description' => 'Desayuno especial estilo chapín',
                'prices_15cm' => ['pickup_capital' => 32, 'domicilio_capital' => 37, 'pickup_interior' => 34, 'domicilio_interior' => 39],
                'prices_30cm' => ['pickup_capital' => 58, 'domicilio_capital' => 63, 'pickup_interior' => 60, 'domicilio_interior' => 65],
            ],
        ];

        foreach ($desayunos as $desayunoData) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $desayunoData['name'],
                'description' => $desayunoData['description'],
                'image' => null,
                'has_variants' => true,
                'precio_pickup_capital' => 0,
                'precio_domicilio_capital' => 0,
                'precio_pickup_interior' => 0,
                'precio_domicilio_interior' => 0,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            // Crear variante 15cm
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => Str::slug($desayunoData['name']).'-15cm',
                'name' => '15cm',
                'size' => '15cm',
                'precio_pickup_capital' => $desayunoData['prices_15cm']['pickup_capital'],
                'precio_domicilio_capital' => $desayunoData['prices_15cm']['domicilio_capital'],
                'precio_pickup_interior' => $desayunoData['prices_15cm']['pickup_interior'],
                'precio_domicilio_interior' => $desayunoData['prices_15cm']['domicilio_interior'],
                'is_active' => true,
                'sort_order' => 1,
            ]);

            // Crear variante 30cm
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => Str::slug($desayunoData['name']).'-30cm',
                'name' => '30cm',
                'size' => '30cm',
                'precio_pickup_capital' => $desayunoData['prices_30cm']['pickup_capital'],
                'precio_domicilio_capital' => $desayunoData['prices_30cm']['domicilio_capital'],
                'precio_pickup_interior' => $desayunoData['prices_30cm']['pickup_interior'],
                'precio_domicilio_interior' => $desayunoData['prices_30cm']['domicilio_interior'],
                'is_active' => true,
                'sort_order' => 2,
            ]);

            // Asociar secciones
            $product->sections()->attach($sections);

            $this->command->line("      ✓ {$product->name} (2 variantes)");
        }
    }
}
