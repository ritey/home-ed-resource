<?php

namespace Database\Seeders;

use App\Enums\AgeBand;
use App\Enums\Tier;
use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Loads database/seeders/data/resources.json. Safe to re-run: rows are
 * matched on url and updated in place, and list order follows the file.
 * Run TaxonomySeeder first; categories and regions are referenced by slug.
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

        $categories = Category::pluck('id', 'slug');
        $regions = Region::pluck('id', 'slug');

        foreach ($rows as $position => $row) {
            // Fail loudly on a typo rather than seed a half-broken entry.
            $validator = Validator::make($row, [
                'title' => ['required', 'string'],
                'url' => ['required', 'url'],
                'domain' => ['required', 'string'],
                'tier' => ['required', Rule::enum(Tier::class)],
                'category' => ['present', 'nullable', Rule::in($categories->keys())],
                'also' => ['sometimes', 'array', 'max:2'],
                'also.*' => ['distinct', Rule::in($categories->keys()), 'different:category'],
                'region' => ['present', 'nullable', Rule::in($regions->keys())],
                'location' => ['sometimes', 'nullable', 'string'],
                'ages' => ['present', 'array'],
                'ages.*' => [Rule::enum(AgeBand::class)],
                'spotlight' => ['sometimes', 'boolean'],
                'description' => ['required', 'string'],
                'more' => ['sometimes', 'array'],
                'more.*' => ['required', 'string'],
                'tags' => ['required', 'array'],
                'tags.*' => ['required', 'string'],
                'last_checked' => ['required', 'date_format:Y-m-d'],
                'image' => ['sometimes', 'nullable', 'string'],
                'image_alt' => ['sometimes', 'nullable', 'string'],
            ], [
                'also.max' => 'a resource can have at most two secondary categories ("also").',
                'also.*.different' => 'a secondary category repeats the primary one.',
            ]);

            if ($validator->fails()) {
                $name = $row['title'] ?? "row {$position}";

                throw new RuntimeException("resources.json: {$name}: ".$validator->errors()->first());
            }

            $data = $validator->validated();

            $resource = Resource::updateOrCreate(['url' => $data['url']], [
                ...collect($data)->except(['category', 'also', 'region'])->all(),
                'category_id' => $categories[$data['category']] ?? null,
                'region_id' => $regions[$data['region']] ?? null,
                'spotlight' => $data['spotlight'] ?? false,
                'position' => $position,
            ]);

            $resource->categories()->sync(
                collect($data['also'] ?? [])->map(fn (string $slug) => $categories[$slug])->all()
            );
        }
    }
}
