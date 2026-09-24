<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use Database\Seeders\ResourceSeeder;
use Database\Seeders\TaxonomySeeder;
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
        $this->seed(TaxonomySeeder::class);
    }

    public function test_it_spotlights_the_seeded_resources_with_their_synopses(): void
    {
        $this->seed(ResourceSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Worth a look', 'iDEA', 'Do Revision'])
            ->assertSee('Little online modules about computers')
            ->assertSee('Inspiring Digital Enterprise Award')
            ->assertSee('<link rel="canonical" href="https://homeedresource.co.uk/">', false);
    }

    public function test_only_flagged_resources_are_spotlit_when_any_are(): void
    {
        Resource::factory()->spotlit()->create(['title' => 'Shown']);
        Resource::factory()->create(['title' => 'Not shown']);

        $this->get('/')->assertSee('Shown')->assertDontSee('Not shown');
    }

    public function test_the_spotlight_falls_back_to_the_most_recently_checked(): void
    {
        Resource::factory()->create(['title' => 'Older', 'last_checked' => '2026-01-01']);
        Resource::factory()->create(['title' => 'Newer', 'last_checked' => '2026-09-01']);

        $this->get('/')->assertSeeInOrder(['Newer', 'Older']);
    }

    public function test_the_pills_are_derived_from_the_data(): void
    {
        Resource::factory()->create(['last_checked' => '2026-09-18']);
        Resource::factory()->create(['last_checked' => '2026-09-11']);

        $this->get('/')
            ->assertSee('2 things so far')
            ->assertSee('Updated 18 September');
    }

    public function test_tiles_show_only_categories_with_resources_and_name_the_rest(): void
    {
        $maths = Category::firstWhere('slug', 'maths');
        $exams = Category::firstWhere('slug', 'exams');
        Resource::factory()->in($exams)->create()->categories()->attach($maths);
        Resource::factory()->in($maths)->create();

        $this->get('/')
            ->assertSee('href="'.route('browse.category', $maths).'"', false)
            ->assertSee('href="'.route('browse.category', $exams).'"', false)
            ->assertSeeInOrder(['Maths', '2 things'])              // primary + secondary
            ->assertDontSee('href="'.route('browse.category', 'history').'"', false)
            ->assertSee('Nothing under Mixed subjects, English &amp; literacy', false);
    }

    public function test_near_you_lists_only_regions_in_use(): void
    {
        Resource::factory()->near(Region::firstWhere('slug', 'south-west'), 'Bristol')->create();

        $this->get('/')
            ->assertSee('href="'.route('browse.region', 'south-west').'"', false)
            ->assertDontSee(route('browse.region', 'london'));
    }

    public function test_near_you_explains_itself_when_nothing_is_local(): void
    {
        Resource::factory()->create();

        $this->get('/')->assertSee('Everything on the list so far works from anywhere.');
    }

    public function test_the_page_still_renders_with_no_resources_at_all(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('0 things so far')
            ->assertDontSee('Updated');
    }

    public function test_the_homepage_search_submits_to_the_list(): void
    {
        $this->get('/')
            ->assertSee('action="'.route('browse').'"', false)
            ->assertSee('name="q"', false);
    }

    public function test_pages_set_no_cookies(): void
    {
        Resource::factory()->in(Category::first())->create();

        foreach (['/', '/resources', '/resources/mixed-subjects', '/resources/near/london', '/llms.txt', '/sitemap.xml', '/robots.txt'] as $path) {
            $this->assertEmpty(
                $this->get($path)->assertOk()->headers->getCookies(),
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
            ->assertDontSee('tabindex="-1"', false);
    }

    public function test_the_structured_data_cannot_break_out_of_its_script_tag(): void
    {
        Resource::factory()->spotlit()->create(['description' => 'Tricky </script><b>x</b>']);
        Resource::factory()->spotlit()->paid()->create();

        $html = $this->get('/')->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $graph = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR)['@graph'];
        $list = collect($graph)->firstWhere('@type', 'ItemList');
        $site = collect($graph)->firstWhere('@type', 'WebSite');

        $this->assertSame(2, $list['numberOfItems']);
        $this->assertSame('Tricky </script><b>x</b>', $list['itemListElement'][0]['item']['description']);
        $this->assertFalse($list['itemListElement'][1]['item']['isAccessibleForFree']);
        $this->assertSame('https://homeedresource.co.uk/resources?q={search_term_string}', $site['potentialAction']['target']['urlTemplate']);
    }
}
