<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://homeedresource.co.uk']);
        $this->seed(TaxonomySeeder::class);
    }

    public function test_llms_txt_is_plain_text_grouped_by_category(): void
    {
        Resource::factory()->in(Category::firstWhere('slug', 'exams'))->create([
            'title' => 'Do Revision',
            'description' => "The plan is the bit we found useful — it's handy.",
            'more' => ['First paragraph.', 'Second paragraph.'],
            'last_checked' => '2026-09-11',
        ]);
        Resource::factory()->create(['title' => 'Uncategorised']);

        $response = $this->get('/llms.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $body = $response->getContent();
        $this->assertStringStartsWith('# Home Ed Resource', $body);
        $this->assertStringContainsString("## Exams & qualifications\n", $body);
        $this->assertStringContainsString('All of them: https://homeedresource.co.uk/resources/exams', $body);
        $this->assertStringContainsString("found useful — it's handy. (Free · Ages 11–16 · Online or UK-wide", $body);
        $this->assertStringContainsString("\n  First paragraph.\n  Second paragraph.\n", $body);
        $this->assertStringContainsString("## Everything else\n", $body);
        $this->assertStringNotContainsString('## Maths', $body);          // empty categories left out
        $this->assertStringNotContainsString('&#039;', $body);
        $this->assertStringNotContainsString('&amp;', $body);
    }

    public function test_the_sitemap_lists_landing_pages_that_have_something_on_them(): void
    {
        $maths = Category::firstWhere('slug', 'maths');
        Resource::factory()->in(Category::firstWhere('slug', 'exams'))->create(['last_checked' => '2026-09-11'])
            ->categories()->attach($maths);
        Resource::factory()->near(Region::firstWhere('slug', 'london'))->create(['last_checked' => '2026-09-18']);

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $urls = [];
        foreach ($xml->url as $u) {
            $urls[(string) $u->loc] = (string) $u->lastmod;
        }

        $this->assertSame('2026-09-18', $urls['https://homeedresource.co.uk/']);
        $this->assertSame('2026-09-18', $urls['https://homeedresource.co.uk/resources']);
        $this->assertSame('2026-09-11', $urls['https://homeedresource.co.uk/resources/exams']);
        $this->assertSame('2026-09-11', $urls['https://homeedresource.co.uk/resources/maths']);   // via secondary
        $this->assertSame('2026-09-18', $urls['https://homeedresource.co.uk/resources/near/london']);
        $this->assertSame('2026-09-18', $urls['https://homeedresource.co.uk/resources/near/england']); // rolls up
        $this->assertArrayNotHasKey('https://homeedresource.co.uk/resources/history', $urls);
        $this->assertArrayNotHasKey('https://homeedresource.co.uk/resources/near/scotland', $urls);
    }

    public function test_robots_points_at_the_sitemap_on_the_configured_host(): void
    {
        config(['app.url' => 'https://www.example.test/']);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: ClaudeBot')
            ->assertSee('Sitemap: https://www.example.test/sitemap.xml');
    }
}
