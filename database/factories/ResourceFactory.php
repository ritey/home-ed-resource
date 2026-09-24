<?php

namespace Database\Factories;

use App\Enums\Tier;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<resource>
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
}
