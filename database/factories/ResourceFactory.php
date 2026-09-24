<?php

namespace Database\Factories;

use App\Enums\AgeBand;
use App\Enums\Tier;
use App\Models\Category;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    public function definition(): array
    {
        $domain = fake()->unique()->domainName();

        return [
            'title' => fake()->words(2, true),
            'url' => "https://{$domain}",
            'domain' => $domain,
            'tier' => Tier::Free,
            'category_id' => null,
            'region_id' => null,
            'location' => null,
            'ages' => [AgeBand::Secondary],
            'spotlight' => false,
            'description' => fake()->sentence(20),
            'more' => null,
            'tags' => ['Teens', 'GCSE'],
            'last_checked' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
            'image' => null,
            'image_alt' => null,
            'position' => 0,
        ];
    }

    public function paid(): static
    {
        return $this->state(['tier' => Tier::Paid]);
    }

    public function spotlit(): static
    {
        return $this->state(['spotlight' => true]);
    }

    public function in(Category $category): static
    {
        return $this->state(['category_id' => $category->id]);
    }

    public function near(Region $region, ?string $location = null): static
    {
        return $this->state(['region_id' => $region->id, 'location' => $location]);
    }
}
