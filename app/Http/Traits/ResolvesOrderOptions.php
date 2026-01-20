<?php

namespace App\Http\Traits;

use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;

trait ResolvesOrderOptions
{
    /**
     * Resuelve los nombres de las opciones seleccionadas
     *
     * @param  array<int, array{section_id: int, option_id: int}>  $selectedOptions
     * @return array<int, array{section_name: string, name: string, price: float}>
     */
    protected function resolveSelectedOptions(array $selectedOptions): array
    {
        if (empty($selectedOptions)) {
            return [];
        }

        $sectionIds = collect($selectedOptions)->pluck('section_id')->unique()->values()->all();
        $optionIds = collect($selectedOptions)->pluck('option_id')->unique()->values()->all();

        $sections = Section::whereIn('id', $sectionIds)->pluck('title', 'id');
        $options = SectionOption::whereIn('id', $optionIds)->get()->keyBy('id');

        return collect($selectedOptions)->map(function ($selection) use ($sections, $options) {
            $option = $options->get($selection['option_id']);

            return [
                'section_name' => $sections->get($selection['section_id'], 'Sección'),
                'name' => $option?->name ?? 'Opción',
                'price' => (float) ($option?->price_modifier ?? 0),
            ];
        })->all();
    }
}
