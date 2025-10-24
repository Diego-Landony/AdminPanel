<?php

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Services\Menu\VariantSyncService;

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Category Variants Management', function () {
    it('can create a category with variants', function () {
        $response = $this->post(route('menu.categories.store'), [
            'name' => 'Pizzas',
            'is_active' => true,
            'is_combo_category' => false,
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm', '45cm'],
        ]);

        $response->assertRedirect(route('menu.categories.index'));

        $category = Category::where('name', 'Pizzas')->first();
        expect($category)->not->toBeNull()
            ->and($category->uses_variants)->toBeTrue()
            ->and($category->variant_definitions)->toBe(['15cm', '30cm', '45cm']);
    });

    it('requires at least one variant when uses_variants is true', function () {
        $response = $this->post(route('menu.categories.store'), [
            'name' => 'Pizzas',
            'is_active' => true,
            'is_combo_category' => false,
            'uses_variants' => true,
            'variant_definitions' => [],
        ]);

        $response->assertSessionHasErrors('variant_definitions');
    });

    it('prevents duplicate variant names', function () {
        $response = $this->post(route('menu.categories.store'), [
            'name' => 'Pizzas',
            'is_active' => true,
            'is_combo_category' => false,
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm', '15cm'],
        ]);

        $response->assertSessionHasErrors('variant_definitions');
    });

    it('can update category variants', function () {
        $category = Category::factory()->create([
            'name' => 'Pizzas',
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $response = $this->put(route('menu.categories.update', $category), [
            'name' => 'Pizzas',
            'is_active' => true,
            'is_combo_category' => false,
            'uses_variants' => true,
            'variant_definitions' => ['Pequeña', 'Mediana', 'Grande'],
        ]);

        $response->assertRedirect(route('menu.categories.index'));

        $category->refresh();
        expect($category->variant_definitions)->toBe(['Pequeña', 'Mediana', 'Grande']);
    });
});

describe('Variant Synchronization', function () {
    it('creates variants for all products when adding a new variant to category', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);

        $variantSync = app(VariantSyncService::class);
        $variantSync->addVariantToCategory($category, '45cm');

        expect(ProductVariant::where('product_id', $product1->id)->where('name', '45cm')->exists())->toBeTrue()
            ->and(ProductVariant::where('product_id', $product2->id)->where('name', '45cm')->exists())->toBeTrue();
    });

    it('renames variants in all products when renaming in category', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
        ]);

        $variantSync = app(VariantSyncService::class);
        $variantSync->renameVariant($category, '15cm', 'Pequeña');

        $variant = ProductVariant::where('product_id', $product->id)->first();
        expect($variant->name)->toBe('Pequeña');
    });

    it('prevents removing variant if in use', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
            'is_active' => true,
        ]);

        $variantSync = app(VariantSyncService::class);

        expect(fn () => $variantSync->removeVariant($category, '15cm'))
            ->toThrow(Exception::class);
    });

    it('syncs category variants to all products on category update', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->put(route('menu.categories.update', $category), [
            'name' => $category->name,
            'is_active' => true,
            'is_combo_category' => false,
            'uses_variants' => true,
            'variant_definitions' => ['Pequeña', 'Mediana', 'Grande'],
        ]);

        $variants = ProductVariant::where('product_id', $product->id)->pluck('name')->toArray();
        expect($variants)->toContain('Pequeña', 'Mediana', 'Grande');
    });
});

describe('Product Creation with Category Variants', function () {
    it('can create product with variants from category', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm', '45cm'],
        ]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Pizza Margherita',
            'description' => 'Classic pizza',
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'name' => '15cm',
                    'precio_pickup_capital' => '50',
                    'precio_domicilio_capital' => '55',
                    'precio_pickup_interior' => '60',
                    'precio_domicilio_interior' => '65',
                ],
                [
                    'name' => '30cm',
                    'precio_pickup_capital' => '100',
                    'precio_domicilio_capital' => '105',
                    'precio_pickup_interior' => '110',
                    'precio_domicilio_interior' => '115',
                ],
            ],
            'sections' => [],
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $product = Product::where('name', 'Pizza Margherita')->first();
        expect($product)->not->toBeNull()
            ->and($product->variants->count())->toBe(2)
            ->and($product->variants->pluck('name')->toArray())->toContain('15cm', '30cm');
    });

    it('requires at least one variant when category uses variants', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Pizza Margherita',
            'is_active' => true,
            'has_variants' => true,
            'variants' => [],
            'sections' => [],
        ]);

        $response->assertSessionHasErrors('category_id');
    });

    it('validates variant names match category definitions', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Pizza Margherita',
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'name' => 'InvalidSize',
                    'precio_pickup_capital' => '50',
                    'precio_domicilio_capital' => '55',
                    'precio_pickup_interior' => '60',
                    'precio_domicilio_interior' => '65',
                ],
            ],
            'sections' => [],
        ]);

        $response->assertSessionHasErrors('variants');
    });

    it('prevents variants on products when category does not use variants', function () {
        $category = Category::factory()->create([
            'uses_variants' => false,
        ]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Pizza Margherita',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => '100',
            'precio_domicilio_capital' => '105',
            'precio_pickup_interior' => '110',
            'precio_domicilio_interior' => '115',
            'variants' => [
                [
                    'name' => '15cm',
                    'precio_pickup_capital' => '50',
                    'precio_domicilio_capital' => '55',
                    'precio_pickup_interior' => '60',
                    'precio_domicilio_interior' => '65',
                ],
            ],
            'sections' => [],
        ]);

        $response->assertSessionHasErrors('category_id');
    });
});

describe('Product Update with Category Variants', function () {
    it('can update product variants', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm', '45cm'],
        ]);

        $product = Product::factory()->create(['category_id' => $category->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
            'precio_pickup_capital' => 50,
        ]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'name' => '30cm',
                    'precio_pickup_capital' => '100',
                    'precio_domicilio_capital' => '105',
                    'precio_pickup_interior' => '110',
                    'precio_domicilio_interior' => '115',
                ],
            ],
            'sections' => [],
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $product->refresh();
        $variants = $product->variants->pluck('name')->toArray();
        expect($variants)->toContain('30cm');
    });

    it('validates updated variants match category definitions', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'name' => 'InvalidSize',
                    'precio_pickup_capital' => '50',
                    'precio_domicilio_capital' => '55',
                    'precio_pickup_interior' => '60',
                    'precio_domicilio_interior' => '65',
                ],
            ],
            'sections' => [],
        ]);

        $response->assertSessionHasErrors('variants');
    });

    it('deactivates variants instead of deleting them when removed from product', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm', '45cm'],
        ]);

        $product = Product::factory()->create(['category_id' => $category->id]);

        // Crear tres variantes activas
        $variant15 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
            'precio_pickup_capital' => 50,
            'precio_domicilio_capital' => 55,
            'precio_pickup_interior' => 60,
            'precio_domicilio_interior' => 65,
            'is_active' => true,
        ]);

        $variant30 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '30cm',
            'precio_pickup_capital' => 100,
            'precio_domicilio_capital' => 105,
            'precio_pickup_interior' => 110,
            'precio_domicilio_interior' => 115,
            'is_active' => true,
        ]);

        $variant45 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '45cm',
            'precio_pickup_capital' => 150,
            'precio_domicilio_capital' => 155,
            'precio_pickup_interior' => 160,
            'precio_domicilio_interior' => 165,
            'is_active' => true,
        ]);

        // Actualizar producto enviando solo la variante 30cm (remover 15cm y 45cm)
        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'id' => $variant30->id,
                    'name' => '30cm',
                    'precio_pickup_capital' => '100',
                    'precio_domicilio_capital' => '105',
                    'precio_pickup_interior' => '110',
                    'precio_domicilio_interior' => '115',
                ],
            ],
            'sections' => [],
        ]);

        $response->assertRedirect(route('menu.products.index'));

        // Verificar que las 3 variantes SIGUEN existiendo (no eliminadas)
        expect(ProductVariant::where('product_id', $product->id)->count())->toBe(3);

        // Verificar que 15cm y 45cm están desactivadas
        $variant15->refresh();
        $variant45->refresh();
        expect($variant15->is_active)->toBeFalse()
            ->and($variant45->is_active)->toBeFalse();

        // Verificar que 30cm sigue activa
        $variant30->refresh();
        expect($variant30->is_active)->toBeTrue();

        // Verificar que los precios se conservan (historial)
        expect($variant15->precio_pickup_capital)->toBe('50.00')
            ->and($variant45->precio_pickup_capital)->toBe('150.00');
    });

    it('reactivates previously deactivated variants when included again', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);

        $product = Product::factory()->create(['category_id' => $category->id]);

        // Crear variante desactivada
        $variant15 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
            'precio_pickup_capital' => 50,
            'precio_domicilio_capital' => 55,
            'precio_pickup_interior' => 60,
            'precio_domicilio_interior' => 65,
            'is_active' => false,
        ]);

        // Actualizar producto incluyendo la variante 15cm
        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'id' => $variant15->id,
                    'name' => '15cm',
                    'precio_pickup_capital' => '55',
                    'precio_domicilio_capital' => '60',
                    'precio_pickup_interior' => '65',
                    'precio_domicilio_interior' => '70',
                ],
            ],
            'sections' => [],
        ]);

        $response->assertRedirect(route('menu.products.index'));

        // Verificar que la variante se reactivó
        $variant15->refresh();
        expect($variant15->is_active)->toBeTrue()
            ->and($variant15->precio_pickup_capital)->toBe('55.00');
    });
});
