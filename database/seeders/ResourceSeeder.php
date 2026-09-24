<?php

namespace Database\Seeders;

use App\Enums\Tier;
use App\Models\Resource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Loads database/seeders/data/resources.json. Safe to re-run: rows are
 * matched on url and updated in place, and list order follows the file.
 */
class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(
            file_get_contents(database_path('seeders/data/resources.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($rows as $position => $row) {
            // Fail loudly on a typo rather than seed a half-broken entry.
            $validator = Validator::make($row, [
                'title' => ['required', 'string'],
                'url' => ['required', 'url'],
                'domain' => ['required', 'string'],
                'tier' => ['required', Rule::enum(Tier::class)],
                'description' => ['required', 'string'],
                'more' => ['sometimes', 'array'],
                'more.*' => ['required', 'string'],
                'tags' => ['required', 'array'],
                'tags.*' => ['required', 'string'],
                'last_checked' => ['required', 'date_format:Y-m-d'],
                'image' => ['sometimes', 'nullable', 'string'],
                'image_alt' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $name = $row['title'] ?? "row {$position}";

                throw new RuntimeException("resources.json: {$name}: ".$validator->errors()->first());
            }

            Resource::updateOrCreate(
                ['url' => $row['url']],
                [...$validator->validated(), 'position' => $position],
            );
        }
    }
}
