<?php

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Product Listing', function () {
    test('can list products grouped by category', function () {
        $category1 = Category::factory()->create(['name' => 'Bebidas', 'sort_order' => 1]);
        $category2 = Category::factory()->create(['name' => 'Subs', 'sort_order' => 2]);

        Product::factory()->count(2)->create(['category_id' => $category1->id]);
        Product::factory()->count(3)->create(['category_id' => $category2->id]);

        $response = $this->get(route('menu.products.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/products/index')
            ->has('groupedProducts', 2)
            ->has('stats')
        );
    });

    test('lists products without category separately', function () {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->count(2)->create(['category_id' => null]);

        $response = $this->get(route('menu.products.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('groupedProducts', 2)
            ->where('groupedProducts.1.category.name', 'Sin categoría')
        );
    });

    test('displays stats correctly', function () {
        Product::factory()->count(5)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->get(route('menu.products.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('stats.total_products', 7)
            ->where('stats.active_products', 5)
        );
    });

    test('loads products with variants', function () {
        $category = Category::factory()->create(['uses_variants' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'has_variants' => true,
        ]);
        ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

        $response = $this->get(route('menu.products.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('groupedProducts.0.products.0.variants', 2)
        );
    });
});

describe('Product Creation Page', function () {
    test('can show create product page', function () {
        $categories = Category::factory()->count(2)->create(['is_combo_category' => false]);
        $sections = Section::factory()->count(2)->create();

        $response = $this->get(route('menu.products.create'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/products/create')
            ->has('categories', 2)
            ->has('sections', 2)
        );
    });

    test('excludes combo categories from create page', function () {
        Category::factory()->create(['is_combo_category' => true, 'is_active' => true]);
        Category::factory()->create(['is_combo_category' => false, 'is_active' => true]);

        $response = $this->get(route('menu.products.create'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('categories', 1)
        );
    });
});

describe('Product Creation', function () {
    test('can create a product without variants', function () {
        $category = Category::factory()->create(['uses_variants' => false]);

        $productData = [
            'category_id' => $category->id,
            'name' => 'Coca Cola',
            'description' => 'Refresco de cola',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 15.00,
            'precio_domicilio_capital' => 18.00,
            'precio_pickup_interior' => 16.00,
            'precio_domicilio_interior' => 19.00,
        ];

        $response = $this->post(route('menu.products.store'), $productData);

        $response->assertRedirect(route('menu.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Coca Cola',
            'category_id' => $category->id,
            'has_variants' => false,
            'precio_pickup_capital' => 15.00,
        ]);
    });

    test('can create a product with variants', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['Chico', 'Grande'],
        ]);

        $productData = [
            'category_id' => $category->id,
            'name' => 'Sub Italiano',
            'description' => 'Sub con jamón y salami',
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'name' => 'Chico',
                    'precio_pickup_capital' => 30.00,
                    'precio_domicilio_capital' => 35.00,
                    'precio_pickup_interior' => 32.00,
                    'precio_domicilio_interior' => 37.00,
                ],
                [
                    'name' => 'Grande',
                    'precio_pickup_capital' => 60.00,
                    'precio_domicilio_capital' => 65.00,
                    'precio_pickup_interior' => 62.00,
                    'precio_domicilio_interior' => 67.00,
                ],
            ],
        ];

        $response = $this->post(route('menu.products.store'), $productData);

        $response->assertRedirect(route('menu.products.index'));

        $product = Product::where('name', 'Sub Italiano')->first();
        expect($product)->not->toBeNull();
        expect($product->variants)->toHaveCount(2);
        expect($product->variants->first()->name)->toBe('Chico');
        expect($product->variants->last()->name)->toBe('Grande');
    });

    test('can create a product with sections', function () {
        $category = Category::factory()->create(['uses_variants' => false]);
        $sections = Section::factory()->count(3)->create();

        $productData = [
            'category_id' => $category->id,
            'name' => 'Sub Personalizable',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
            'sections' => $sections->pluck('id')->toArray(),
        ];

        $response = $this->post(route('menu.products.store'), $productData);

        $response->assertRedirect(route('menu.products.index'));

        $product = Product::where('name', 'Sub Personalizable')->first();
        expect($product->sections)->toHaveCount(3);
    });

    test('can create a product with image', function () {
        Storage::fake('public');
        $category = Category::factory()->create(['uses_variants' => false]);

        $productData = [
            'category_id' => $category->id,
            'name' => 'Producto con Imagen',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 25.00,
            'precio_domicilio_capital' => 28.00,
            'precio_pickup_interior' => 26.00,
            'precio_domicilio_interior' => 29.00,
            'image' => UploadedFile::fake()->image('product.jpg'),
        ];

        $response = $this->post(route('menu.products.store'), $productData);

        $response->assertRedirect(route('menu.products.index'));

        $product = Product::where('name', 'Producto con Imagen')->first();
        expect($product->image)->toContain('/storage/images/');
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $product->image));
    });

    test('sets sort_order automatically', function () {
        $category = Category::factory()->create(['uses_variants' => false]);

        $productData = [
            'category_id' => $category->id,
            'name' => 'Producto Ordenado',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 25.00,
            'precio_domicilio_capital' => 28.00,
            'precio_pickup_interior' => 26.00,
            'precio_domicilio_interior' => 29.00,
        ];

        $response = $this->post(route('menu.products.store'), $productData);

        $response->assertRedirect(route('menu.products.index'));

        $product = Product::where('name', 'Producto Ordenado')->first();
        expect($product->sort_order)->toBeGreaterThanOrEqual(0);
    });
});

describe('Product Edit Page', function () {
    test('can show edit product page', function () {
        $product = Product::factory()->create();
        $categories = Category::factory()->count(2)->create(['is_combo_category' => false]);
        $sections = Section::factory()->count(2)->create();

        $response = $this->get(route('menu.products.edit', $product));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/products/edit')
            ->has('product')
            ->has('categories', 2)
            ->has('sections', 2)
        );
    });

    test('loads product with category and sections', function () {
        $category = Category::factory()->create();
        $sections = Section::factory()->count(2)->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $product->sections()->attach($sections);

        $response = $this->get(route('menu.products.edit', $product));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('product.category.id', $category->id)
            ->has('product.sections', 2)
        );
    });

    test('loads product with variants', function () {
        $category = Category::factory()->create(['uses_variants' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'has_variants' => true,
        ]);
        ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

        $response = $this->get(route('menu.products.edit', $product));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('product.variants', 2)
        );
    });
});

describe('Product Update', function () {
    test('can update a product', function () {
        $category = Category::factory()->create(['uses_variants' => false]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Nombre Original',
            'has_variants' => false,
        ]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Nombre Actualizado',
            'description' => 'Nueva descripción',
            'is_active' => false,
            'has_variants' => false,
            'precio_pickup_capital' => 20.00,
            'precio_domicilio_capital' => 25.00,
            'precio_pickup_interior' => 22.00,
            'precio_domicilio_interior' => 27.00,
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nombre Actualizado',
            'is_active' => false,
            'precio_pickup_capital' => 20.00,
        ]);
    });

    test('can update product variants', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['15cm', '30cm'],
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'has_variants' => true,
        ]);
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '30cm',
        ]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'id' => $variant1->id,
                    'name' => '15cm',
                    'precio_pickup_capital' => 35.00,
                    'precio_domicilio_capital' => 40.00,
                    'precio_pickup_interior' => 37.00,
                    'precio_domicilio_interior' => 42.00,
                ],
                [
                    'id' => $variant2->id,
                    'name' => '30cm',
                    'precio_pickup_capital' => 65.00,
                    'precio_domicilio_capital' => 70.00,
                    'precio_pickup_interior' => 67.00,
                    'precio_domicilio_interior' => 72.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant1->id,
            'precio_pickup_capital' => 35.00,
        ]);
    });

    test('can add new variants to existing product', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['Pequeño', 'Mediano', 'Grande'],
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'has_variants' => true,
        ]);
        $existingVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Pequeño',
        ]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'id' => $existingVariant->id,
                    'name' => 'Pequeño',
                    'precio_pickup_capital' => 25.00,
                    'precio_domicilio_capital' => 28.00,
                    'precio_pickup_interior' => 26.00,
                    'precio_domicilio_interior' => 29.00,
                ],
                [
                    'name' => 'Grande',
                    'precio_pickup_capital' => 50.00,
                    'precio_domicilio_capital' => 55.00,
                    'precio_pickup_interior' => 52.00,
                    'precio_domicilio_interior' => 57.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $product->refresh();
        expect($product->variants()->where('is_active', true)->count())->toBe(2);
    });

    test('can update product sections', function () {
        $category = Category::factory()->create(['uses_variants' => false]);
        $product = Product::factory()->create(['category_id' => $category->id]);
        $oldSections = Section::factory()->count(2)->create();
        $product->sections()->attach($oldSections);

        $newSections = Section::factory()->count(3)->create();

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
            'sections' => $newSections->pluck('id')->toArray(),
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $product->refresh();
        expect($product->sections)->toHaveCount(3);
    });

    test('can update product image', function () {
        Storage::fake('public');
        $category = Category::factory()->create(['uses_variants' => false]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'image' => '/storage/images/old-image.jpg',
        ]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
            'image' => UploadedFile::fake()->image('new-image.jpg'),
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $product->refresh();
        expect($product->image)->toContain('/storage/images/');
        expect($product->image)->not->toContain('old-image.jpg');
    });

    test('can remove product image', function () {
        Storage::fake('public');
        $category = Category::factory()->create(['uses_variants' => false]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'image' => '/storage/images/test-image.jpg',
        ]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
            'remove_image' => true,
        ]);

        $response->assertRedirect(route('menu.products.index'));

        $product->refresh();
        expect($product->image)->toBeNull();
    });

    test('switching from variants to no variants removes variants', function () {
        $category = Category::factory()->create(['uses_variants' => false]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'has_variants' => true,
        ]);
        ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

        $response = $this->put(route('menu.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
        ]);

        $response->assertRedirect(route('menu.products.index'));

        expect(ProductVariant::where('product_id', $product->id)->count())->toBe(0);
    });
});

describe('Product Deletion', function () {
    test('can delete a product', function () {
        $product = Product::factory()->create();

        $response = $this->delete(route('menu.products.destroy', $product));

        $response->assertRedirect(route('menu.products.index'));

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    });

    test('cannot delete product used in combo items', function () {
        $product = Product::factory()->create();
        $combo = \App\Models\Menu\Combo::factory()->create();
        $combo->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'label' => 'Sub',
            'sort_order' => 1,
        ]);

        $response = $this->delete(route('menu.products.destroy', $product));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    });

    test('cannot delete product used in combo choice groups', function () {
        $menuStructure = createMenuStructureForComboTests();
        $product = $menuStructure['products'][0];
        $combo = \App\Models\Menu\Combo::factory()->create([
            'category_id' => $menuStructure['comboCategory']->id,
        ]);

        $comboItem = $combo->items()->create([
            'product_id' => $menuStructure['bebida']->id,
            'quantity' => 1,
            'label' => 'Elige tu sub',
            'sort_order' => 1,
            'is_choice_group' => true,
        ]);

        $comboItem->options()->create([
            'product_id' => $product->id,
            'variant_id' => $product->variants->first()->id,
            'sort_order' => 1,
        ]);

        $response = $this->delete(route('menu.products.destroy', $product));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    });

    test('deletes product variants when deleting product', function () {
        $category = Category::factory()->create(['uses_variants' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'has_variants' => true,
        ]);
        $variants = ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

        $response = $this->delete(route('menu.products.destroy', $product));

        $response->assertRedirect(route('menu.products.index'));

        foreach ($variants as $variant) {
            $this->assertSoftDeleted('product_variants', [
                'id' => $variant->id,
            ]);
        }
    });
});

describe('Product Reordering', function () {
    test('can reorder products', function () {
        $products = Product::factory()->count(3)->create();

        $reversedProducts = $products->reverse()->values();
        $newOrder = $reversedProducts->map(fn ($product, $index) => [
            'id' => $product->id,
            'sort_order' => $index,
        ])->toArray();

        $response = $this->post(route('menu.products.reorder'), [
            'products' => $newOrder,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($newOrder as $item) {
            $this->assertDatabaseHas('products', [
                'id' => $item['id'],
                'sort_order' => $item['sort_order'],
            ]);
        }
    });
});

describe('Product Validation', function () {
    test('validates required fields on store', function () {
        $response = $this->post(route('menu.products.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'category_id']);
    });

    test('validates required fields on update', function () {
        $product = Product::factory()->create();

        $response = $this->put(route('menu.products.update', $product), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'category_id']);
    });

    test('validates prices are required when has_variants is false', function () {
        $category = Category::factory()->create(['uses_variants' => false]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'is_active' => true,
            'has_variants' => false,
        ]);

        $response->assertSessionHasErrors([
            'precio_pickup_capital',
            'precio_domicilio_capital',
            'precio_pickup_interior',
            'precio_domicilio_interior',
        ]);
    });

    test('validates variants are required when category uses variants', function () {
        $category = Category::factory()->create(['uses_variants' => true]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'is_active' => true,
            'has_variants' => true,
            'variants' => [],
        ]);

        $response->assertSessionHasErrors(['category_id']);
    });

    test('validates variant names match category definitions', function () {
        $category = Category::factory()->create([
            'uses_variants' => true,
            'variant_definitions' => ['Pequeño', 'Grande'],
        ]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'is_active' => true,
            'has_variants' => true,
            'variants' => [
                [
                    'name' => 'Mediano',
                    'precio_pickup_capital' => 30.00,
                    'precio_domicilio_capital' => 35.00,
                    'precio_pickup_interior' => 32.00,
                    'precio_domicilio_interior' => 37.00,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['variants']);
    });

    test('validates category exists', function () {
        $response = $this->post(route('menu.products.store'), [
            'category_id' => 99999,
            'name' => 'Test Product',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors(['category_id']);
    });

    test('validates sections exist', function () {
        $category = Category::factory()->create(['uses_variants' => false]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
            'sections' => [99999],
        ]);

        $response->assertSessionHasErrors(['sections.0']);
    });

    test('validates image file type', function () {
        Storage::fake('public');
        $category = Category::factory()->create(['uses_variants' => false]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertSessionHasErrors(['image']);
    });

    test('validates image file size', function () {
        Storage::fake('public');
        $category = Category::factory()->create(['uses_variants' => false]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 52.00,
            'precio_domicilio_interior' => 57.00,
            'image' => UploadedFile::fake()->create('large-image.jpg', 6000),
        ]);

        $response->assertSessionHasErrors(['image']);
    });
});

describe('Product Authentication', function () {
    test('requires authentication for index', function () {
        auth()->logout();

        $response = $this->get(route('menu.products.index'));

        $response->assertRedirect(route('login'));
    });

    test('requires authentication for create', function () {
        auth()->logout();

        $response = $this->get(route('menu.products.create'));

        $response->assertRedirect(route('login'));
    });

    test('requires authentication for store', function () {
        auth()->logout();
        $category = Category::factory()->create(['uses_variants' => false]);

        $response = $this->post(route('menu.products.store'), [
            'category_id' => $category->id,
            'name' => 'Test Product',
        ]);

        $response->assertRedirect(route('login'));
    });

    test('requires authentication for edit', function () {
        auth()->logout();
        $product = Product::factory()->create();

        $response = $this->get(route('menu.products.edit', $product));

        $response->assertRedirect(route('login'));
    });

    test('requires authentication for update', function () {
        auth()->logout();
        $product = Product::factory()->create();

        $response = $this->put(route('menu.products.update', $product), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect(route('login'));
    });

    test('requires authentication for destroy', function () {
        auth()->logout();
        $product = Product::factory()->create();

        $response = $this->delete(route('menu.products.destroy', $product));

        $response->assertRedirect(route('login'));
    });
});
