<?php

namespace Tests\Feature;

use App\Models\Resource;
use Database\Seeders\ResourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.url' => 'https://homeedresource.co.uk']);
    }

    public function test_it_lists_the_seeded_resources_with_their_synopses(): void
    {
        $this->seed(ResourceSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['iDEA', 'Do Revision'])
            ->assertSee('Inspiring Digital Enterprise Award')
            ->assertSee('AQA (37 subjects, 813 modules)')
            ->assertSee('<link rel="canonical" href="https://homeedresource.co.uk/">', false);
    }

    public function test_the_pills_are_derived_from_the_data(): void
    {
        Resource::factory()->create(['last_checked' => '2026-09-18']);
        Resource::factory()->create(['last_checked' => '2026-09-11']);

        $this->get('/')
            ->assertSee('2 things so far')
            ->assertSee('Updated 18 September');
    }

    public function test_an_empty_tier_shows_its_explanation_instead_of_entries(): void
    {
        Resource::factory()->create();

        $this->get('/')
            ->assertSee(config('site.tiers.paid.empty'))
            ->assertDontSee(config('site.tiers.free.empty'));
    }

    public function test_the_page_still_renders_with_no_resources_at_all(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('0 things so far')
            ->assertDontSee('Updated');
    }

    public function test_pages_set_no_cookies(): void
    {
        Resource::factory()->create();

        foreach (['/', '/llms.txt', '/sitemap.xml', '/robots.txt'] as $path) {
            $this->assertEmpty(
                $this->get($path)->headers->getCookies(),
                "{$path} set a cookie",
            );
        }
    }

    public function test_the_contact_address_never_appears_whole_in_the_markup(): void
    {
        $this->get('/')
            ->assertDontSee(config('site.email'))
            ->assertSee('data-u="hello"', false)
            ->assertSee('data-d="homeedresource.co.uk"', false);
    }

    public function test_a_thumbnail_without_alt_text_is_hidden_from_assistive_tech(): void
    {
        Resource::factory()->create(['image' => 'images/a.png', 'image_alt' => null]);

        $this->get('/')
            ->assertSee('tabindex="-1" aria-hidden="true"', false)
            ->assertSee('alt=""', false);
    }

    public function test_a_thumbnail_with_alt_text_is_left_announceable(): void
    {
        Resource::factory()->create(['image' => 'images/a.png', 'image_alt' => 'A screenshot']);

        $this->get('/')
            ->assertSee('alt="A screenshot"', false)
            ->assertDontSee('aria-hidden="true" >', false)
            ->assertDontSee('tabindex="-1"', false);
    }

    public function test_the_structured_data_lists_every_resource_and_cannot_break_out_of_its_script_tag(): void
    {
        Resource::factory()->create(['description' => 'Tricky </script><b>x</b>']);
        Resource::factory()->paid()->create();

        $html = $this->get('/')->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $graph = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR)['@graph'];
        $list = collect($graph)->firstWhere('@type', 'ItemList');

        $this->assertSame(2, $list['numberOfItems']);
        $this->assertSame('Tricky </script><b>x</b>', $list['itemListElement'][0]['item']['description']);
        $this->assertTrue($list['itemListElement'][0]['item']['isAccessibleForFree']);
        $this->assertFalse($list['itemListElement'][1]['item']['isAccessibleForFree']);
    }
}
