<?php

namespace Database\Seeders;

use App\Enums\CategoryGroup;
use App\Models\Category;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Loads categories.json and regions.json. Safe to re-run: matched on slug,
 * and order follows the files.
 */
class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->read('categories.json') as $position => $row) {
            $data = $this->validate('categories.json', $row, [
                // "near" is taken by /resources/near/{region}.
                'slug' => ['required', 'alpha_dash', 'not_in:near'],
                'name' => ['required', 'string'],
                'group' => ['required', Rule::enum(CategoryGroup::class)],
                'blurb' => ['required', 'string'],
            ]);

            Category::updateOrCreate(['slug' => $data['slug']], [...$data, 'position' => $position]);
        }

        $position = 0;
        foreach ($this->read('regions.json') as $nation) {
            $parent = $this->region($nation, null, $position++);

            foreach ($nation['regions'] ?? [] as $child) {
                $this->region($child, $parent, $position++);
            }
        }
    }

    private function region(array $row, ?Region $parent, int $position): Region
    {
        $data = $this->validate('regions.json', $row, [
            'slug' => ['required', 'alpha_dash'],
            'name' => ['required', 'string'],
            'phrase' => ['required', 'string'],
        ]);

        return Region::updateOrCreate(['slug' => $data['slug']], [
            ...$data,
            'parent_id' => $parent?->id,
            'position' => $position,
        ]);
    }

    private function read(string $file): array
    {
        return json_decode(
            file_get_contents(database_path("seeders/data/{$file}")),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    private function validate(string $file, array $row, array $rules): array
    {
        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            $name = $row['slug'] ?? '?';

            throw new RuntimeException("{$file}: {$name}: ".$validator->errors()->first());
        }

        return $validator->validated();
    }
}
