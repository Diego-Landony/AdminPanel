<?php

use App\Models\Menu\Category;
use App\Models\User;

beforeEach(function () {
    // Usar el helper createTestUser que crea un usuario con todos los permisos
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('puede listar categorías ordenadas por sort_order', function () {
    Category::factory()->count(3)->create();

    $response = $this->get(route('menu.categories.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('menu/categories/index')
        ->has('categories', 3)
        ->has('stats')
    );
});

test('puede crear una categoría', function () {
    $categoryData = [
        'name' => 'Bebidas Frías',
        'uses_variants' => true,
        'is_active' => true,
    ];

    $response = $this->post(route('menu.categories.store'), $categoryData);

    $response->assertRedirect(route('menu.categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Bebidas Frías',
        'uses_variants' => true,
        'is_active' => true,
    ]);
});

test('valida campos requeridos al crear categoría', function () {
    $response = $this->post(route('menu.categories.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('puede editar una categoría', function () {
    $category = Category::factory()->create([
        'name' => 'Nombre Original',
    ]);

    $response = $this->put(route('menu.categories.update', $category), [
        'name' => 'Nombre Actualizado',
        'uses_variants' => false,
        'is_active' => false,
    ]);

    $response->assertRedirect(route('menu.categories.index'));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Nombre Actualizado',
        'uses_variants' => false,
        'is_active' => false,
    ]);
});

test('puede eliminar una categoría', function () {
    $category = Category::factory()->create();

    $response = $this->delete(route('menu.categories.destroy', $category));

    $response->assertRedirect(route('menu.categories.index'));

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('puede actualizar sort_order de múltiples categorías', function () {
    $categories = Category::factory()->count(3)->create();

    $reversedCategories = $categories->reverse()->values();
    $newOrder = $reversedCategories->map(fn ($category, $index) => [
        'id' => $category->id,
        'sort_order' => $index,
    ])->toArray();

    $response = $this->post(route('menu.categories.reorder'), [
        'categories' => $newOrder,
    ]);

    $response->assertRedirect();

    foreach ($newOrder as $item) {
        $this->assertDatabaseHas('categories', [
            'id' => $item['id'],
            'sort_order' => $item['sort_order'],
        ]);
    }
});
