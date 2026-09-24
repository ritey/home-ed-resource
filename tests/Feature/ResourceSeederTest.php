<?php

namespace Tests\Feature;

use App\Enums\AgeBand;
use App\Enums\Tier;
use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ResourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ResourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_safe_to_run_twice_and_keeps_file_order(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(13, Category::count());
        $this->assertSame(13, Region::count());
        $this->assertSame(['iDEA', 'Do Revision'], Resource::ordered()->pluck('title')->all());

        $idea = Resource::with('category', 'categories')->firstWhere('url', 'https://idea.org.uk');
        $this->assertSame(Tier::Free, $idea->tier);
        $this->assertSame('computing-digital', $idea->category->slug);
        $this->assertSame(['life-skills'], $idea->categories->pluck('slug')->all());
        $this->assertTrue($idea->ages->contains(AgeBand::Primary));
        $this->assertTrue($idea->spotlight);
        $this->assertNull($idea->region_id);
        $this->assertCount(3, $idea->more);
    }

    /**
     * @return array<string, array{array, string}>
     */
    public static function badRows(): array
    {
        return [
            'three secondary categories' => [['also' => ['maths', 'history', 'languages']], 'at most two secondary'],
            'secondary repeats primary' => [['category' => 'maths', 'also' => ['maths']], 'repeats the primary'],
            'unknown category' => [['category' => 'astrology'], 'category'],
            'unknown region' => [['region' => 'atlantis'], 'region'],
            'unknown age band' => [['ages' => ['toddlers']], 'ages'],
        ];
    }

    #[DataProvider('badRows')]
    public function test_it_rejects_bad_rows_loudly(array $override, string $message): void
    {
        $this->seed(DatabaseSeeder::class);

        $path = database_path('seeders/data/resources.json');
        $original = File::get($path);
        $rows = json_decode($original, true);
        $rows[0] = [...$rows[0], ...$override];

        try {
            File::put($path, json_encode($rows));
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);
            $this->seed(ResourceSeeder::class);
        } finally {
            File::put($path, $original);
        }
    }
}
