<?php

namespace Tests\Feature;

use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://homeedresource.co.uk']);
    }

    public function test_llms_txt_is_plain_text_with_synopses_and_no_html_entities(): void
    {
        Resource::factory()->create([
            'title' => 'Do Revision',
            'description' => "The plan is the bit we found useful — it's handy.",
            'more' => ['First paragraph.', 'Second paragraph.'],
            'last_checked' => '2026-09-11',
        ]);

        $response = $this->get('/llms.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $body = $response->getContent();
        $this->assertStringStartsWith('# Home Ed Resource', $body);
        $this->assertStringContainsString("found useful — it's handy.", $body);
        $this->assertStringContainsString("\n  First paragraph.\n  Second paragraph.\n", $body);
        $this->assertStringContainsString('Last updated: 2026-09-11', $body);
        $this->assertStringNotContainsString('&#039;', $body);
    }

    public function test_the_sitemap_lastmod_is_the_most_recent_check(): void
    {
        Resource::factory()->create(['last_checked' => '2026-09-11']);
        Resource::factory()->create(['last_checked' => '2026-09-18']);

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $this->assertSame('https://homeedresource.co.uk/', (string) $xml->url->loc);
        $this->assertSame('2026-09-18', (string) $xml->url->lastmod);
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
