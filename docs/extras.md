El plan está completo. Aquí está el resumen:
Resumen del Plan
Funcionalidad: Descuento por cantidad en extras, solo aplica a extras del MISMO precio. Configuración por sección:
bundle_discount_enabled - Activa/desactiva
bundle_size - Cantidad para bundle (default: 2)
bundle_discount_amount - Descuento por cada bundle (ej: Q5)
Ejemplo:
Extras: Queso (Q11), Tocino (Q11), Hongos (Q11), Doble carne (Q16)
Grupo Q11: 3 items → 1 bundle (-Q5) = Q33 - Q5 = Q28
Grupo Q16: 1 item → sin bundle = Q16
Total: Q44 (ahorro Q5)
Archivos principales a modificar:
Section.php - Nuevo método calculateOptionsPrice()
CartItem.php - Nuevos métodos para bundle pricing
create.tsx y edit.tsx - UI para configurar bundle
Nueva migración para campos en sections
Claude’s Plan
Plan: Descuento por Cantidad en Extras (Bundle Pricing)
Resumen del Requerimiento
Implementar un sistema de descuento por cantidad en las secciones de extras donde:
Solo aplica a extras del MISMO PRECIO
2 extras del mismo precio → Se aplica descuento fijo (ej: -Q5)
3 extras del mismo precio → Descuento aplica a 2, el tercero a precio normal
4 extras del mismo precio → Se aplica el descuento 2 veces
Mostrar el ahorro al cliente en la UI
Fórmula:

Por cada grupo de extras con mismo price_modifier:
  bundles = floor(cantidad_en_grupo / bundle_size)
  descuento_grupo = bundles * bundle_discount_amount
  total_grupo = (suma_precios_grupo) - descuento_grupo

total = suma de todos los total_grupo
ahorro = suma de todos los descuento_grupo
Ejemplo con descuento Q5 por cada 2 del mismo precio:
Selección: Queso (Q11), Tocino (Q11), Hongos (Q11), Doble carne (Q16)
Grupo Q11: 3 items = Q33, 1 bundle → Q33 - Q5 = Q28
Grupo Q16: 1 item = Q16, sin bundle → Q16
Total: Q44 (sin descuento sería Q49)
Ahorro: Q5
Decisión de Diseño
Configuración a nivel de sección con descuento fijo por par:
bundle_discount_enabled - Activa/desactiva el descuento
bundle_size - Cantidad de items del mismo precio para formar bundle (default: 2)
bundle_discount_amount - Monto de descuento por cada bundle formado (ej: Q5)
Archivos a Modificar
Backend
Archivo	Acción
database/migrations/xxxx_add_bundle_pricing_to_sections_table.php	CREAR
app/Models/Menu/Section.php	MODIFICAR
app/Models/CartItem.php	MODIFICAR
app/Http/Requests/Menu/StoreSectionRequest.php	MODIFICAR
app/Http/Requests/Menu/UpdateSectionRequest.php	MODIFICAR
app/Http/Resources/Api/V1/Menu/SectionResource.php	VERIFICAR
app/Http/Resources/Api/V1/Cart/CartItemResource.php	MODIFICAR (mostrar ahorro)
app/Traits/FormatsSelectedOptions.php	MODIFICAR (incluir info de bundle)
Frontend
Archivo	Acción
resources/js/types/menu.ts	MODIFICAR
resources/js/Pages/menu/sections/create.tsx	MODIFICAR
resources/js/Pages/menu/sections/edit.tsx	MODIFICAR
Tests
Archivo	Acción
tests/Feature/Cart/CartBundlePricingTest.php	CREAR
Plan de Implementación Detallado
Paso 1: Migración de Base de Datos
Crear migración para agregar campos a sections:

Schema::table('sections', function (Blueprint $table) {
    $table->boolean('bundle_discount_enabled')->default(false)->after('max_selections');
    $table->unsignedTinyInteger('bundle_size')->default(2)->after('bundle_discount_enabled');
    $table->decimal('bundle_discount_amount', 8, 2)->nullable()->after('bundle_size');
});
Campos:
bundle_discount_enabled - Activa/desactiva el descuento por cantidad
bundle_size - Cantidad de items del MISMO PRECIO para formar bundle (default: 2)
bundle_discount_amount - Monto de descuento por cada bundle (ej: Q5)
Paso 2: Actualizar Modelo Section

// app/Models/Menu/Section.php

protected $fillable = [
    // ... existentes ...
    'bundle_discount_enabled',
    'bundle_size',
    'bundle_discount_amount',
];

protected function casts(): array
{
    return [
        // ... existentes ...
        'bundle_discount_enabled' => 'boolean',
        'bundle_size' => 'integer',
        'bundle_discount_amount' => 'decimal:2',
    ];
}

/**
 * Calcula el precio total y ahorro de las opciones seleccionadas.
 * Agrupa por precio y aplica descuento solo a extras del mismo precio.
 *
 * @return array{total: float, savings: float, details: array}
 */
public function calculateOptionsPrice(array $selectedOptionIds): array
{
    $options = $this->options()->whereIn('id', $selectedOptionIds)->get();

    // Solo considerar opciones con is_extra = true
    $extras = $options->where('is_extra', true);

    // Suma de opciones sin extra (precio 0)
    $nonExtrasTotal = $options->where('is_extra', false)->sum('price_modifier');

    if (!$this->bundle_discount_enabled || $extras->count() < $this->bundle_size) {
        // Sin bundle: sumar todos los precios
        $total = $extras->sum('price_modifier') + $nonExtrasTotal;
        return ['total' => $total, 'savings' => 0.0, 'details' => []];
    }

    // Agrupar extras por price_modifier
    $groupedByPrice = $extras->groupBy('price_modifier');

    $total = 0.0;
    $savings = 0.0;
    $details = [];

    foreach ($groupedByPrice as $price => $group) {
        $count = $group->count();
        $groupTotal = $count * (float)$price;

        // Calcular bundles para este grupo de precio
        $bundles = intdiv($count, $this->bundle_size);
        $groupSavings = $bundles * (float)$this->bundle_discount_amount;

        $total += $groupTotal - $groupSavings;
        $savings += $groupSavings;

        $details[] = [
            'price' => (float)$price,
            'count' => $count,
            'bundles' => $bundles,
            'savings' => $groupSavings,
        ];
    }

    // Agregar opciones sin extra
    $total += $nonExtrasTotal;

    return [
        'total' => round($total, 2),
        'savings' => round($savings, 2),
        'details' => $details,
    ];
}
Paso 3: Modificar CartItem para Bundle Pricing
El método actual suma todos los price_modifier. Necesitamos modificarlo para usar la lógica de bundle y retornar el ahorro:

// app/Models/CartItem.php

/**
 * Calcula el total de opciones con bundle pricing.
 *
 * @return array{total: float, savings: float}
 */
public function getOptionsTotalWithBundle(): array
{
    if (!$this->selected_options || !is_array($this->selected_options)) {
        return ['total' => 0.0, 'savings' => 0.0];
    }

    // Agrupar opciones por section_id
    $optionsBySectionId = collect($this->selected_options)->groupBy('section_id');

    $total = 0.0;
    $savings = 0.0;

    foreach ($optionsBySectionId as $sectionId => $options) {
        $optionIds = $options->pluck('option_id')->filter()->unique()->toArray();

        if (empty($optionIds)) continue;

        $section = Section::find($sectionId);
        if (!$section) continue;

        // Usar el nuevo método que aplica bundle pricing
        $result = $section->calculateOptionsPrice($optionIds);
        $total += $result['total'];
        $savings += $result['savings'];
    }

    return [
        'total' => round($total, 2),
        'savings' => round($savings, 2),
    ];
}

/**
 * Mantener compatibilidad con código existente.
 */
public function getOptionsTotal(): float
{
    return $this->getOptionsTotalWithBundle()['total'];
}

/**
 * Obtener el ahorro por bundle.
 */
public function getBundleSavings(): float
{
    return $this->getOptionsTotalWithBundle()['savings'];
}
Paso 4: Actualizar Validaciones

// app/Http/Requests/Menu/StoreSectionRequest.php y UpdateSectionRequest.php

public function rules(): array
{
    return [
        // ... reglas existentes ...
        'bundle_discount_enabled' => 'boolean',
        'bundle_size' => 'required_if:bundle_discount_enabled,true|integer|min:2|max:10',
        'bundle_discount_amount' => 'required_if:bundle_discount_enabled,true|nullable|numeric|min:0.01',
    ];
}

public function messages(): array
{
    return [
        'bundle_size.required_if' => 'La cantidad para bundle es requerida cuando el descuento está habilitado.',
        'bundle_size.min' => 'La cantidad para bundle debe ser al menos 2.',
        'bundle_discount_amount.required_if' => 'El monto de descuento es requerido cuando el descuento está habilitado.',
        'bundle_discount_amount.min' => 'El monto de descuento debe ser mayor a 0.',
    ];
}
Paso 5: Actualizar SectionController

// app/Http/Controllers/Menu/SectionController.php

// En store() y update(), asegurar que los nuevos campos se guarden
$validated = $request->validated();

// Si bundle no está habilitado, limpiar campos relacionados
if (!($validated['bundle_discount_enabled'] ?? false)) {
    $validated['bundle_size'] = 2;
    $validated['bundle_discount_amount'] = null;
}
Paso 6: Actualizar API Resources
SectionResource (para que la app cliente tenga la info de bundle):

// app/Http/Resources/Api/V1/Menu/SectionResource.php

public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'description' => $this->description,
        'is_required' => $this->is_required,
        'allow_multiple' => $this->allow_multiple,
        'min_selections' => $this->min_selections,
        'max_selections' => $this->max_selections,
        'bundle_discount_enabled' => $this->bundle_discount_enabled,
        'bundle_size' => $this->bundle_size,
        'bundle_discount_amount' => $this->bundle_discount_amount ? (float) $this->bundle_discount_amount : null,
        'options' => SectionOptionResource::collection($this->whenLoaded('options')),
    ];
}
CartItemResource (para mostrar ahorro al cliente):

// app/Http/Resources/Api/V1/Cart/CartItemResource.php

public function toArray(Request $request): array
{
    $bundleResult = $this->getOptionsTotalWithBundle();
    $optionsTotal = $bundleResult['total'];
    $bundleSavings = $bundleResult['savings'];

    // ... código existente ...

    return [
        // ... campos existentes ...
        'options_total' => $optionsTotal,
        'bundle_savings' => $bundleSavings,  // NUEVO: mostrar ahorro
        // ...
    ];
}
Paso 7: Actualizar Tipos TypeScript

// resources/js/types/menu.ts

interface Section {
    id: number;
    title: string;
    description: string | null;
    is_required: boolean;
    allow_multiple: boolean;
    min_selections: number;
    max_selections: number;
    is_active: boolean;
    sort_order: number;
    // Nuevos campos para bundle pricing
    bundle_discount_enabled: boolean;
    bundle_size: number;
    bundle_discount_amount: number | null;
    options?: SectionOption[];
}

// También agregar en tipos de carrito si existe:
interface CartItem {
    // ... campos existentes ...
    bundle_savings?: number;  // Ahorro por bundle
}
Paso 8: Actualizar UI de Crear/Editar Sección
Agregar al formulario de sección:

// resources/js/Pages/menu/sections/create.tsx y edit.tsx

// Nuevo estado
const [formData, setFormData] = useState({
    // ... existentes ...
    bundle_discount_enabled: false,
    bundle_size: 2,
    bundle_discount_amount: '',
});

// Nueva sección en el formulario (mostrar solo si hay opciones con is_extra)
{formData.options.some(opt => opt.is_extra) && (
    <Card>
        <CardHeader>
            <CardTitle>Descuento por Cantidad</CardTitle>
            <CardDescription>
                Descuento cuando se seleccionan múltiples extras del mismo precio
            </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
            <div className="flex items-center space-x-2">
                <Checkbox
                    id="bundle_discount_enabled"
                    checked={formData.bundle_discount_enabled}
                    onCheckedChange={(checked) =>
                        setFormData(prev => ({ ...prev, bundle_discount_enabled: !!checked }))
                    }
                />
                <Label htmlFor="bundle_discount_enabled">
                    Habilitar descuento por cantidad
                </Label>
            </div>

            {formData.bundle_discount_enabled && (
                <div className="grid grid-cols-2 gap-4 pl-6">
                    <div className="space-y-2">
                        <Label>Cantidad para descuento</Label>
                        <Input
                            type="number"
                            min="2"
                            max="10"
                            value={formData.bundle_size}
                            onChange={(e) =>
                                setFormData(prev => ({ ...prev, bundle_size: parseInt(e.target.value) || 2 }))
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            Cada {formData.bundle_size} extras del mismo precio reciben descuento
                        </p>
                    </div>
                    <div className="space-y-2">
                        <Label>Descuento por bundle (Q)</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={formData.bundle_discount_amount}
                            onChange={(e) =>
                                setFormData(prev => ({ ...prev, bundle_discount_amount: e.target.value }))
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            Ej: Si extras cuestan Q11 c/u y descuento es Q5, 2 extras = Q17
                        </p>
                    </div>
                </div>
            )}
        </CardContent>
    </Card>
)}
Paso 9: Tests

// tests/Feature/Cart/CartBundlePricingTest.php

it('applies bundle discount to extras of same price', function () {
    // Crear sección con bundle pricing: descuento Q5 por cada 2 del mismo precio
    $section = Section::factory()->create([
        'bundle_discount_enabled' => true,
        'bundle_size' => 2,
        'bundle_discount_amount' => 5.00,
    ]);

    // Crear 4 opciones con precio Q11 cada una
    $options = SectionOption::factory()->count(4)->create([
        'section_id' => $section->id,
        'is_extra' => true,
        'price_modifier' => 11.00,
    ]);

    // 2 extras del mismo precio → Q22 - Q5 = Q17
    $result2 = $section->calculateOptionsPrice([$options[0]->id, $options[1]->id]);
    expect($result2['total'])->toBe(17.00);
    expect($result2['savings'])->toBe(5.00);

    // 3 extras → Q33, 1 bundle → Q33 - Q5 = Q28
    $result3 = $section->calculateOptionsPrice([
        $options[0]->id, $options[1]->id, $options[2]->id
    ]);
    expect($result3['total'])->toBe(28.00);
    expect($result3['savings'])->toBe(5.00);

    // 4 extras → Q44, 2 bundles → Q44 - Q10 = Q34
    $result4 = $section->calculateOptionsPrice($options->pluck('id')->toArray());
    expect($result4['total'])->toBe(34.00);
    expect($result4['savings'])->toBe(10.00);
});

it('only applies bundle to extras of same price', function () {
    $section = Section::factory()->create([
        'bundle_discount_enabled' => true,
        'bundle_size' => 2,
        'bundle_discount_amount' => 5.00,
    ]);

    // Doble carne Q16
    $option1 = SectionOption::factory()->create([
        'section_id' => $section->id,
        'is_extra' => true,
        'price_modifier' => 16.00,
    ]);

    // Queso y Tocino Q11 cada uno
    $option2 = SectionOption::factory()->create([
        'section_id' => $section->id,
        'is_extra' => true,
        'price_modifier' => 11.00,
    ]);
    $option3 = SectionOption::factory()->create([
        'section_id' => $section->id,
        'is_extra' => true,
        'price_modifier' => 11.00,
    ]);

    // Seleccionar: Doble carne (Q16), Queso (Q11), Tocino (Q11)
    // Grupo Q16: 1 item, sin bundle
    // Grupo Q11: 2 items, 1 bundle → Q22 - Q5 = Q17
    // Total: Q16 + Q17 = Q33
    $result = $section->calculateOptionsPrice([
        $option1->id, $option2->id, $option3->id
    ]);

    expect($result['total'])->toBe(33.00);
    expect($result['savings'])->toBe(5.00);  // Solo del grupo Q11
});

it('does not apply bundle when disabled', function () {
    $section = Section::factory()->create([
        'bundle_discount_enabled' => false,
    ]);

    $options = SectionOption::factory()->count(2)->create([
        'section_id' => $section->id,
        'is_extra' => true,
        'price_modifier' => 11.00,
    ]);

    $result = $section->calculateOptionsPrice($options->pluck('id')->toArray());

    expect($result['total'])->toBe(22.00);
    expect($result['savings'])->toBe(0.00);
});

it('does not apply bundle when count is less than bundle_size', function () {
    $section = Section::factory()->create([
        'bundle_discount_enabled' => true,
        'bundle_size' => 2,
        'bundle_discount_amount' => 5.00,
    ]);

    $option = SectionOption::factory()->create([
        'section_id' => $section->id,
        'is_extra' => true,
        'price_modifier' => 11.00,
    ]);

    // Solo 1 extra, no aplica bundle
    $result = $section->calculateOptionsPrice([$option->id]);

    expect($result['total'])->toBe(11.00);
    expect($result['savings'])->toBe(0.00);
});
Decisiones Confirmadas
Pregunta	Decisión
Precio individual para sobrantes	Precio del item específico
Precios mixtos	Solo aplica a extras del MISMO precio
UI para cliente	Mostrar ahorro
Configuración	Descuento fijo por par (no precio fijo de bundle)
Orden de Implementación
Migración de BD (bundle_discount_enabled, bundle_size, bundle_discount_amount)
Modelo Section (campos, casts, método calculateOptionsPrice)
Validaciones (StoreSectionRequest, UpdateSectionRequest)
SectionController (store/update)
Tests de backend
CartItem (getOptionsTotalWithBundle, getBundleSavings)
CartItemResource (incluir bundle_savings)
Tipos TypeScript
UI de crear/editar sección
Ejecutar tests y pint