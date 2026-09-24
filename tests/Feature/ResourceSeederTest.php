<?php

namespace Tests\Feature;

use App\Enums\Tier;
use App\Models\Resource;
use Database\Seeders\ResourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_safe_to_run_twice_and_keeps_file_order(): void
    {
        $this->seed(ResourceSeeder::class);
        $this->seed(ResourceSeeder::class);

        $this->assertSame(['iDEA', 'Do Revision'], Resource::ordered()->pluck('title')->all());

        $idea = Resource::firstWhere('url', 'https://idea.org.uk');
        $this->assertSame(Tier::Free, $idea->tier);
        $this->assertCount(3, $idea->more);
        $this->assertSame('2026-09-18', $idea->last_checked->toDateString());
    }
}
