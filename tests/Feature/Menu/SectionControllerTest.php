<?php

use App\Models\Menu\Product;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Section Listing', function () {
    test('displays sections list with statistics', function () {
        Section::factory(5)->create();

        $response = $this->get(route('menu.sections.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/sections/index')
            ->has('sections', 5)
            ->has('stats')
        );
    });

    test('calculates statistics correctly', function () {
        Section::factory(3)->create(['is_required' => true]);
        Section::factory(2)->create(['is_required' => false]);
        Section::factory()->create(['is_required' => false])->options()->createMany([
            ['name' => 'Opción 1'],
            ['name' => 'Opción 2'],
        ]);

        $response = $this->get(route('menu.sections.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('stats.total_sections', 6)
            ->where('stats.required_sections', 3)
        );
    });

    test('sections are ordered by sort_order', function () {
        Section::factory()->create(['title' => 'Tercero', 'sort_order' => 3]);
        Section::factory()->create(['title' => 'Primero', 'sort_order' => 1]);
        Section::factory()->create(['title' => 'Segundo', 'sort_order' => 2]);

        $response = $this->get(route('menu.sections.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('sections.0.title', 'Primero')
            ->where('sections.1.title', 'Segundo')
            ->where('sections.2.title', 'Tercero')
        );
    });
});

describe('Section Creation', function () {
    test('renders create page', function () {
        $response = $this->get(route('menu.sections.create'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/sections/create')
        );
    });

    test('can create section without options', function () {
        $data = [
            'title' => 'Tipo de Pan',
            'description' => 'Selecciona tu pan favorito',
            'is_required' => true,
            'allow_multiple' => false,
            'min_selections' => 1,
            'max_selections' => 1,
            'is_active' => true,
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertRedirect(route('menu.sections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sections', [
            'title' => 'Tipo de Pan',
            'is_required' => true,
        ]);
    });

    test('can create section with options', function () {
        $data = [
            'title' => 'Verduras',
            'description' => 'Elige tus verduras',
            'is_required' => false,
            'allow_multiple' => true,
            'min_selections' => 0,
            'max_selections' => 5,
            'is_active' => true,
            'options' => [
                ['name' => 'Lechuga', 'is_extra' => false, 'price_modifier' => 0],
                ['name' => 'Tomate', 'is_extra' => false, 'price_modifier' => 0],
                ['name' => 'Aguacate', 'is_extra' => true, 'price_modifier' => 5.00],
            ],
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertRedirect();

        $section = Section::where('title', 'Verduras')->first();
        expect($section)->not->toBeNull();
        expect($section->options)->toHaveCount(3);
        expect($section->options->first()->name)->toBe('Lechuga');
    });

});

describe('Section Validation', function () {
    test('validates required fields', function (array $data, array $expectedErrors) {
        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertSessionHasErrors($expectedErrors);
    })->with([
        'title required' => [[], ['title']],
        'min_selections required' => [['title' => 'Test'], ['min_selections']],
        'max_selections required' => [['title' => 'Test', 'min_selections' => 0], ['max_selections']],
    ]);

    test('validates title max length', function () {
        $data = [
            'title' => str_repeat('a', 151), // Exceeds 150 max
            'min_selections' => 0,
            'max_selections' => 1,
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertSessionHasErrors('title');
    });

    test('validates min_selections is non-negative', function () {
        $data = [
            'title' => 'Test',
            'min_selections' => -1,
            'max_selections' => 1,
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertSessionHasErrors('min_selections');
    });

    test('validates max_selections is at least 1', function () {
        $data = [
            'title' => 'Test',
            'min_selections' => 0,
            'max_selections' => 0,
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertSessionHasErrors('max_selections');
    });

    test('validates option names are required', function () {
        $data = [
            'title' => 'Test',
            'min_selections' => 0,
            'max_selections' => 1,
            'options' => [
                ['name' => ''], // Empty name
            ],
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertSessionHasErrors('options.0.name');
    });

    test('validates option name max length', function () {
        $data = [
            'title' => 'Test',
            'min_selections' => 0,
            'max_selections' => 1,
            'options' => [
                ['name' => str_repeat('a', 101)], // Exceeds 100 max
            ],
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertSessionHasErrors('options.0.name');
    });

    test('validates price_modifier is non-negative', function () {
        $data = [
            'title' => 'Test',
            'min_selections' => 0,
            'max_selections' => 1,
            'options' => [
                ['name' => 'Test', 'price_modifier' => -5.00],
            ],
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertSessionHasErrors('options.0.price_modifier');
    });
});

describe('Section Updates', function () {
    test('renders edit page with section data', function () {
        $section = Section::factory()->create();
        $section->options()->create(['name' => 'Opción 1']);

        $response = $this->get(route('menu.sections.edit', $section));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/sections/edit')
            ->where('section.id', $section->id)
            ->has('section.options', 1)
        );
    });

    test('can update section keeping same options', function () {
        $section = Section::factory()->create(['title' => 'Original']);
        $section->options()->create(['name' => 'Opción 1']);

        $data = [
            'title' => 'Actualizado',
            'is_required' => $section->is_required,
            'allow_multiple' => $section->allow_multiple,
            'min_selections' => $section->min_selections,
            'max_selections' => $section->max_selections,
            'is_active' => $section->is_active,
            'options' => [
                ['name' => 'Opción 1'],
            ],
        ];

        $response = $this->put(route('menu.sections.update', $section), $data);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('menu.sections.index'));

        $section->refresh();
        expect($section->title)->toBe('Actualizado');
        expect($section->options)->toHaveCount(1);
    });

    test('deletes old options and creates new ones on update', function () {
        $section = Section::factory()->create();
        $oldOption = $section->options()->create(['name' => 'Vieja Opción']);

        $data = [
            'title' => $section->title,
            'is_required' => $section->is_required,
            'allow_multiple' => $section->allow_multiple,
            'min_selections' => $section->min_selections,
            'max_selections' => $section->max_selections,
            'is_active' => $section->is_active,
            'options' => [
                ['name' => 'Nueva Opción 1'],
                ['name' => 'Nueva Opción 2'],
            ],
        ];

        $this->put(route('menu.sections.update', $section), $data);

        $section->refresh();

        // Old option deleted
        expect(SectionOption::find($oldOption->id))->toBeNull();

        // New options created
        expect($section->options)->toHaveCount(2);
        expect($section->options->pluck('name')->toArray())->toContain('Nueva Opción 1');
    });

    test('can update to remove all options', function () {
        $section = Section::factory()->create();
        $section->options()->createMany([
            ['name' => 'Opción 1'],
            ['name' => 'Opción 2'],
        ]);

        $data = [
            'title' => $section->title,
            'is_required' => $section->is_required,
            'allow_multiple' => $section->allow_multiple,
            'min_selections' => $section->min_selections,
            'max_selections' => $section->max_selections,
            'is_active' => $section->is_active,
            'options' => [],
        ];

        $this->put(route('menu.sections.update', $section), $data);

        $section->refresh();
        expect($section->options)->toHaveCount(0);
    });
});

describe('Section Deletion', function () {
    test('can delete unused section', function () {
        $section = Section::factory()->create();

        $response = $this->delete(route('menu.sections.destroy', $section));

        $response->assertRedirect(route('menu.sections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    });

    test('cannot delete section in use by products', function () {
        $section = Section::factory()->create();
        $product = Product::factory()->create();

        $product->sections()->attach($section->id);

        $response = $this->delete(route('menu.sections.destroy', $section));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No se puede eliminar la sección porque está siendo usada por productos.');

        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    });

    test('deletes section options when section is deleted', function () {
        $section = Section::factory()->create();
        $option = $section->options()->create(['name' => 'Test Option']);

        $this->delete(route('menu.sections.destroy', $section));

        // Verify cascade delete
        expect(SectionOption::find($option->id))->toBeNull();
    });
});

describe('Section Options', function () {
    test('section option has correct price modifier when is_extra is true', function () {
        $section = Section::factory()->create();
        $option = $section->options()->create([
            'name' => 'Aguacate',
            'is_extra' => true,
            'price_modifier' => 10.50,
        ]);

        expect($option->getPriceModifier())->toBe(10.50);
    });

    test('section option returns zero price modifier when is_extra is false', function () {
        $section = Section::factory()->create();
        $option = $section->options()->create([
            'name' => 'Lechuga',
            'is_extra' => false,
            'price_modifier' => 10.50, // Set but shouldn't apply
        ]);

        expect($option->getPriceModifier())->toBe(0.0);
    });

    test('section options are ordered by sort_order', function () {
        $section = Section::factory()->create();
        $section->options()->create(['name' => 'Tercero', 'sort_order' => 2]);
        $section->options()->create(['name' => 'Primero', 'sort_order' => 0]);
        $section->options()->create(['name' => 'Segundo', 'sort_order' => 1]);

        $section->refresh();
        $names = $section->options->pluck('name')->toArray();

        expect($names)->toBe(['Primero', 'Segundo', 'Tercero']);
    });
});

describe('Price Modifiers', function () {
    test('creates option with price modifier', function () {
        $data = [
            'title' => 'Extras',
            'is_required' => false,
            'allow_multiple' => true,
            'min_selections' => 0,
            'max_selections' => 3,
            'is_active' => true,
            'options' => [
                ['name' => 'Queso Extra', 'is_extra' => true, 'price_modifier' => 8.50],
                ['name' => 'Tocino', 'is_extra' => true, 'price_modifier' => 12.00],
            ],
        ];

        $this->post(route('menu.sections.store'), $data);

        $section = Section::where('title', 'Extras')->first();
        $queso = $section->options->where('name', 'Queso Extra')->first();

        expect($queso->is_extra)->toBeTrue();
        expect((float) $queso->price_modifier)->toBe(8.50);
        expect($queso->getPriceModifier())->toBe(8.50);
    });

    test('creates option without price modifier', function () {
        $data = [
            'title' => 'Pan',
            'is_required' => true,
            'allow_multiple' => false,
            'min_selections' => 1,
            'max_selections' => 1,
            'is_active' => true,
            'options' => [
                ['name' => 'Blanco', 'is_extra' => false],
                ['name' => 'Integral', 'is_extra' => false],
            ],
        ];

        $this->post(route('menu.sections.store'), $data);

        $section = Section::where('title', 'Pan')->first();
        $blanco = $section->options->where('name', 'Blanco')->first();

        expect($blanco->is_extra)->toBeFalse();
        expect($blanco->getPriceModifier())->toBe(0.0);
    });

    test('price modifier stored as decimal with 2 places', function () {
        $section = Section::factory()->create();
        $option = $section->options()->create([
            'name' => 'Test',
            'is_extra' => true,
            'price_modifier' => 15.999, // Should round to 2 decimals
        ]);

        $option->refresh();
        expect((float) $option->price_modifier)->toBe(16.00);
    });
});

describe('Section Reorder', function () {
    test('can reorder multiple sections', function () {
        $section1 = Section::factory()->create(['sort_order' => 1]);
        $section2 = Section::factory()->create(['sort_order' => 2]);
        $section3 = Section::factory()->create(['sort_order' => 3]);

        $data = [
            'sections' => [
                ['id' => $section3->id, 'sort_order' => 1],
                ['id' => $section1->id, 'sort_order' => 2],
                ['id' => $section2->id, 'sort_order' => 3],
            ],
        ];

        $response = $this->post(route('menu.sections.reorder'), $data);

        $response->assertRedirect();

        expect(Section::find($section3->id)->sort_order)->toBe(1);
        expect(Section::find($section1->id)->sort_order)->toBe(2);
        expect(Section::find($section2->id)->sort_order)->toBe(3);
    });

    test('reorder validates required fields', function () {
        $response = $this->post(route('menu.sections.reorder'), []);

        $response->assertSessionHasErrors('sections');
    });

    test('reorder validates section id exists', function () {
        $data = [
            'sections' => [
                ['id' => 99999, 'sort_order' => 1], // Non-existent ID
            ],
        ];

        $response = $this->post(route('menu.sections.reorder'), $data);

        $response->assertSessionHasErrors('sections.0.id');
    });
});

describe('Usage Tracking', function () {
    test('displays products using the section', function () {
        $section = Section::factory()->create();
        $product = Product::factory()->create();
        $product->sections()->attach($section->id);

        $response = $this->get(route('menu.sections.usage', $section));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/sections/usage')
            ->has('section.products', 1)
        );
    });

    test('shows section details on usage page', function () {
        $section = Section::factory()->create(['title' => 'Test Section']);

        $response = $this->get(route('menu.sections.usage', $section));

        $response->assertInertia(fn ($page) => $page
            ->where('section.title', 'Test Section')
        );
    });
});

describe('Show Section', function () {
    test('displays section details with products', function () {
        $section = Section::factory()->create();
        $section->options()->create(['name' => 'Opción 1']);

        $products = Product::factory(3)->create(['is_active' => true]);
        $products->each(fn ($p) => $p->sections()->attach($section->id));

        $response = $this->get(route('menu.sections.show', $section));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/sections/show')
            ->where('section.id', $section->id)
            ->has('section.options', 1)
            ->has('section.products', 3)
        );
    });

    test('limits products to 10 on show page', function () {
        $section = Section::factory()->create();
        $products = Product::factory(15)->create();
        $products->each(fn ($p) => $p->sections()->attach($section->id));

        $response = $this->get(route('menu.sections.show', $section));

        $response->assertInertia(fn ($page) => $page
            ->has('section.products', 10)
        );
    });
});

describe('Permissions', function () {
    test('user without menu.sections.view cannot access index', function () {
        $user = createTestUserWithPermissions([]);
        $this->actingAs($user);

        $response = $this->get(route('menu.sections.index'));

        $response->assertRedirect(route('no-access'));
    });

    test('user without menu.sections.create cannot access create', function () {
        $user = createTestUserWithPermissions(['menu.sections.view']);
        $this->actingAs($user);

        $response = $this->get(route('menu.sections.create'));

        $response->assertRedirect(route('home'));
    });

    test('user without menu.sections.edit cannot update', function () {
        $user = createTestUserWithPermissions(['menu.sections.view']);
        $this->actingAs($user);

        $section = Section::factory()->create();

        $response = $this->put(route('menu.sections.update', $section), [
            'title' => 'Updated',
            'min_selections' => 0,
            'max_selections' => 1,
        ]);

        $response->assertRedirect(route('home'));
    });

    test('user without menu.sections.delete cannot delete', function () {
        $user = createTestUserWithPermissions(['menu.sections.view']);
        $this->actingAs($user);

        $section = Section::factory()->create();

        $response = $this->delete(route('menu.sections.destroy', $section));

        $response->assertRedirect(route('home'));
    });
});

describe('Edge Cases', function () {
    test('handles section with min_selections equal to max_selections', function () {
        $data = [
            'title' => 'Exactly One',
            'is_required' => true,
            'allow_multiple' => false,
            'min_selections' => 2,
            'max_selections' => 2,
            'is_active' => true,
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertRedirect();

        $section = Section::where('title', 'Exactly One')->first();
        expect($section->min_selections)->toBe(2);
        expect($section->max_selections)->toBe(2);
    });

    test('handles section with is_required but no options', function () {
        $data = [
            'title' => 'Required Empty',
            'is_required' => true,
            'allow_multiple' => false,
            'min_selections' => 1,
            'max_selections' => 1,
            'is_active' => true,
            'options' => [],
        ];

        $response = $this->post(route('menu.sections.store'), $data);

        $response->assertRedirect();

        $section = Section::where('title', 'Required Empty')->first();
        expect($section->options)->toHaveCount(0);
    });
});
