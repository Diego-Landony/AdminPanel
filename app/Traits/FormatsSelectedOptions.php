<?php

namespace App\Traits;

use App\Models\Menu\ComboItem;
use App\Models\Menu\ComboItemOption;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use Illuminate\Support\Collection;

/**
 * Trait para formatear opciones seleccionadas en Resources.
 *
 * Proporciona lógica reutilizable para transformar opciones seleccionadas
 * con batch loading y ordenamiento por sort_order.
 */
trait FormatsSelectedOptions
{
    /**
     * Format selected options with section and option names.
     * Uses batch loading for scalability.
     * Options are sorted by section sort_order for consistent display.
     * Price is calculated from the SectionOption's price_modifier (only for extras).
     *
     * @param  array|null  $selectedOptions  Las opciones seleccionadas del item
     * @return array<int, array<string, mixed>>
     */
    protected function formatSelectedOptions(?array $selectedOptions = null): array
    {
        $options = $selectedOptions ?? $this->selected_options;

        if (! $options) {
            return [];
        }

        $optionsCollection = collect($options);

        // Colectar todos los IDs únicos para batch loading
        $sectionIds = $optionsCollection->pluck('section_id')->filter()->unique()->values()->toArray();
        $optionIds = $optionsCollection->pluck('option_id')->filter()->unique()->values()->toArray();

        // Batch load secciones con sort_order
        $sections = $this->batchLoadSections($sectionIds);

        // Batch load opciones completas para obtener name, is_extra y price_modifier
        $sectionOptions = $this->batchLoadSectionOptions($optionIds);

        // Mapear con los nombres, sort_order y precio real
        $formattedOptions = $optionsCollection->map(function ($option) use ($sections, $sectionOptions) {
            return $this->formatSingleOption($option, $sections, $sectionOptions);
        });

        // Ordenar por sort_order de la sección y remover campo interno
        return $this->sortAndCleanOptions($formattedOptions);
    }

    /**
     * Batch load sections by IDs.
     */
    protected function batchLoadSections(array $sectionIds): Collection
    {
        if (empty($sectionIds)) {
            return collect();
        }

        return Section::whereIn('id', $sectionIds)->get()->keyBy('id');
    }

    /**
     * Batch load section options by IDs.
     */
    protected function batchLoadSectionOptions(array $optionIds): Collection
    {
        if (empty($optionIds)) {
            return collect();
        }

        return SectionOption::whereIn('id', $optionIds)->get()->keyBy('id');
    }

    /**
     * Format a single option with section and price data.
     *
     * @return array<string, mixed>
     */
    protected function formatSingleOption(array $option, Collection $sections, Collection $sectionOptions): array
    {
        $sectionId = $option['section_id'] ?? null;
        $optionId = $option['option_id'] ?? null;
        $section = $sectionId ? ($sections[$sectionId] ?? null) : null;
        $sectionOption = $optionId ? ($sectionOptions[$optionId] ?? null) : null;

        // Obtener precio desde la opción de la DB (getPriceModifier considera is_extra)
        $price = $sectionOption?->getPriceModifier() ?? 0;

        return [
            'section_id' => $sectionId,
            'section_name' => $section?->title,
            'section_sort_order' => $section?->sort_order ?? 999,
            'option_id' => $optionId,
            'option_name' => $option['name'] ?? $sectionOption?->name,
            'price' => (float) $price,
        ];
    }

    /**
     * Sort options by section sort_order and remove internal fields.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function sortAndCleanOptions(Collection $options): array
    {
        return $options
            ->sortBy('section_sort_order')
            ->map(function ($option) {
                unset($option['section_sort_order']);

                return $option;
            })
            ->values()
            ->toArray();
    }

    /**
     * Calculate total price of extras from selected options.
     * Uses batch loading for efficiency.
     *
     * @param  array|null  $selectedOptions  Las opciones seleccionadas
     */
    protected function calculateOptionsTotal(?array $selectedOptions = null): float
    {
        $options = $selectedOptions ?? $this->selected_options ?? [];

        if (empty($options) || ! is_array($options)) {
            return 0.0;
        }

        $optionIds = collect($options)
            ->pluck('option_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($optionIds)) {
            return 0.0;
        }

        $sectionOptions = SectionOption::whereIn('id', $optionIds)->get()->keyBy('id');

        $total = 0.0;
        foreach ($options as $option) {
            $optionId = $option['option_id'] ?? null;
            if ($optionId) {
                $sectionOption = $sectionOptions[$optionId] ?? null;
                $total += $sectionOption?->getPriceModifier() ?? 0;
            }
        }

        return round($total, 2);
    }

    /**
     * Format combo selections with product names and formatted options.
     * Uses batch loading for scalability.
     *
     * @param  array|null  $comboSelections  Las selecciones del combo
     * @return array<int, array<string, mixed>>
     */
    protected function formatComboSelections(?array $comboSelections): array
    {
        if (empty($comboSelections)) {
            return [];
        }

        $selectionsCollection = collect($comboSelections);

        // Collect all combo_item_ids and option_ids for batch loading
        $comboItemIds = $selectionsCollection->pluck('combo_item_id')->filter()->unique()->values()->toArray();

        $optionIds = $selectionsCollection->flatMap(function ($selection) {
            return collect($selection['selections'] ?? [])->pluck('option_id');
        })->filter()->unique()->values()->toArray();

        // Batch load combo items with their labels
        $comboItems = $this->batchLoadComboItems($comboItemIds);

        // Batch load combo item options with products and variants
        $comboItemOptions = $this->batchLoadComboItemOptions($optionIds);

        // Format each selection
        return $selectionsCollection->map(function ($selection) use ($comboItems, $comboItemOptions) {
            $comboItemId = $selection['combo_item_id'] ?? null;
            $comboItem = $comboItemId ? ($comboItems[$comboItemId] ?? null) : null;

            return [
                'combo_item_id' => $comboItemId,
                'choice_label' => $comboItem?->choice_label,
                'selections' => collect($selection['selections'] ?? [])->map(function ($sel) use ($comboItemOptions) {
                    $optionId = $sel['option_id'] ?? null;
                    $option = $optionId ? ($comboItemOptions[$optionId] ?? null) : null;
                    $product = $option?->product;
                    $variant = $option?->variant;

                    return [
                        'option_id' => $optionId,
                        'product' => $product ? [
                            'id' => $product->id,
                            'name' => $product->name,
                            'category_name' => $product->category?->name,
                            'image_url' => $product->getImageUrl(),
                            'variant' => $variant ? [
                                'id' => $variant->id,
                                'name' => $variant->name,
                            ] : null,
                        ] : null,
                        'selected_options' => $this->formatSelectedOptions($sel['selected_options'] ?? []),
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * Batch load combo items by IDs.
     */
    protected function batchLoadComboItems(array $comboItemIds): Collection
    {
        if (empty($comboItemIds)) {
            return collect();
        }

        return ComboItem::whereIn('id', $comboItemIds)->get()->keyBy('id');
    }

    /**
     * Batch load combo item options with products, variants and categories.
     */
    protected function batchLoadComboItemOptions(array $optionIds): Collection
    {
        if (empty($optionIds)) {
            return collect();
        }

        return ComboItemOption::with(['product.category', 'variant'])
            ->whereIn('id', $optionIds)
            ->get()
            ->keyBy('id');
    }
}
