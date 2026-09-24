<?php

namespace Tests\Feature;

use App\Enums\AgeBand;
use App\Http\Controllers\BrowseController;
use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.url' => 'https://homeedresource.co.uk']);
        $this->seed(TaxonomySeeder::class);
    }

    private function category(string $slug): Category
    {
        return Category::firstWhere('slug', $slug);
    }

    private function region(string $slug): Region
    {
        return Region::firstWhere('slug', $slug);
    }

    public function test_the_full_list_shows_everything_with_synopses(): void
    {
        Resource::factory()->create(['title' => 'Alpha', 'more' => ['A longer synopsis.']]);
        Resource::factory()->create(['title' => 'Beta']);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('2 things')
            ->assertSee('Alpha')->assertSee('Beta')
            ->assertSee('A longer synopsis.')
            ->assertSee('<meta name="robots" content="index, follow', false)
            ->assertSee('<link rel="canonical" href="https://homeedresource.co.uk/resources">', false);
    }

    public function test_search_needs_every_word_and_looks_beyond_the_title(): void
    {
        Resource::factory()->create(['title' => 'Do Revision', 'description' => 'GCSE maths and science', 'tags' => []]);
        Resource::factory()->create(['title' => 'Maths Games', 'description' => 'Times tables', 'tags' => ['Primary']]);
        Resource::factory()->create(['title' => 'Coding Club', 'description' => 'Scratch', 'tags' => ['GCSE']]);

        $this->get('/resources?q=gcse+maths')
            ->assertSee('Do Revision')
            ->assertDontSee('Maths Games')
            ->assertDontSee('Coding Club')
            ->assertSee('matching “gcse maths”', false);

        $this->get('/resources?q=GCSE')->assertSee('Coding Club');   // tags
    }

    public function test_search_matches_category_and_place_names(): void
    {
        Resource::factory()->in($this->category('languages'))->create(['title' => 'Parlez']);
        Resource::factory()->near($this->region('south-west'), 'Bristol')->create(['title' => 'Local group']);

        $this->get('/resources?q=languages')->assertSee('Parlez')->assertDontSee('Local group');
        $this->get('/resources?q=bristol')->assertSee('Local group')->assertDontSee('Parlez');
        $this->get('/resources?q=south+west')->assertSee('Local group');
    }

    public function test_like_wildcards_in_a_search_are_treated_as_spaces(): void
    {
        Resource::factory()->create(['title' => 'Anything']);

        $this->get('/resources?q=%25')->assertSee('Anything');
    }

    public function test_the_category_filter_includes_secondary_categories(): void
    {
        $maths = $this->category('maths');
        Resource::factory()->in($maths)->create(['title' => 'Primary maths']);
        Resource::factory()->in($this->category('exams'))->create(['title' => 'Also maths'])->categories()->attach($maths);
        Resource::factory()->in($this->category('history'))->create(['title' => 'History only']);

        $this->get('/resources?category=maths')
            ->assertSee('Primary maths')
            ->assertSee('Also maths')
            ->assertDontSee('History only')
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('<link rel="canonical" href="https://homeedresource.co.uk/resources">', false);
    }

    public function test_a_region_includes_its_nation_but_not_online_resources(): void
    {
        Resource::factory()->near($this->region('south-west'))->create(['title' => 'In Bristol']);
        Resource::factory()->near($this->region('england'))->create(['title' => 'All England']);
        Resource::factory()->near($this->region('london'))->create(['title' => 'In London']);
        Resource::factory()->create(['title' => 'Online thing']);

        $this->get('/resources?where=south-west')
            ->assertSee('In Bristol')->assertSee('All England')
            ->assertDontSee('In London')->assertDontSee('Online thing');

        $this->get('/resources?where=england')
            ->assertSee('In Bristol')->assertSee('All England')->assertSee('In London')
            ->assertDontSee('Online thing');

        $this->get('/resources?where=online')
            ->assertSee('Online thing')->assertDontSee('In Bristol');
    }

    public function test_age_and_cost_filters_combine(): void
    {
        Resource::factory()->create(['title' => 'Free teens', 'ages' => [AgeBand::Secondary]]);
        Resource::factory()->paid()->create(['title' => 'Paid teens', 'ages' => [AgeBand::Secondary]]);
        Resource::factory()->create(['title' => 'Free littles', 'ages' => [AgeBand::EarlyYears]]);

        $this->get('/resources?age=secondary&cost=free')
            ->assertSee('Free teens')
            ->assertDontSee('Paid teens')
            ->assertDontSee('Free littles');
    }

    public function test_unknown_or_malformed_filters_are_ignored_not_errors(): void
    {
        Resource::factory()->create(['title' => 'Still here']);

        $this->get('/resources?category=nope&where=atlantis&age=ancient&cost=cheap&q[]=x')
            ->assertOk()
            ->assertSee('Still here')
            ->assertSee('<meta name="robots" content="index, follow', false);
    }

    public function test_nothing_matching_says_so_and_the_paid_filter_explains_itself(): void
    {
        Resource::factory()->create();

        $this->get('/resources?q=zzzz')->assertSee('Nothing matches that yet.');
        $this->get('/resources?cost=paid')->assertSee(config('site.tiers.paid.empty'));
    }

    public function test_the_filters_keep_their_values_and_the_form_is_plain_get(): void
    {
        $this->get('/resources?category=maths&age=primary&cost=free&where=london&q=times')
            ->assertSee('<form class="filters" action="'.route('browse').'" method="get"', false)
            ->assertSee('value="times"', false)
            ->assertSee('<option value="maths" selected>', false)
            ->assertSee('<option value="primary" selected>', false)
            ->assertSee('<option value="free" selected>', false)
            ->assertSee('<option value="london" selected>', false);
    }

    public function test_a_category_landing_page_is_the_list_filtered_and_indexable(): void
    {
        $history = $this->category('history');
        Resource::factory()->in($history)->create(['title' => 'Castles']);
        Resource::factory()->create(['title' => 'Elsewhere']);

        $this->get('/resources/history')
            ->assertOk()
            ->assertSee('<h1>History</h1>', false)
            ->assertSee($history->blurb)
            ->assertSee('Castles')->assertDontSee('Elsewhere')
            ->assertSee('<title>History resources for home education | Home Ed Resource</title>', false)
            ->assertSee('<link rel="canonical" href="https://homeedresource.co.uk/resources/history">', false)
            ->assertSee('<meta name="robots" content="index, follow', false)
            ->assertSee('"@type": "BreadcrumbList"', false);
    }

    public function test_a_region_landing_page_names_the_place(): void
    {
        Resource::factory()->near($this->region('north-east'), 'Durham')->create(['title' => 'Durham group']);

        $this->get('/resources/near/north-east')
            ->assertOk()
            ->assertSee('Home ed in the North East')
            ->assertSee('plus anything that covers the whole of England')
            ->assertSee('Durham group')
            ->assertSee('Durham, North East');
    }

    public function test_unknown_landing_pages_are_404s(): void
    {
        $this->get('/resources/astrology')->assertNotFound();
        $this->get('/resources/near/atlantis')->assertNotFound();
    }

    public function test_entries_show_cost_category_ages_and_place(): void
    {
        Resource::factory()->paid()->in($this->category('maths'))
            ->near($this->region('london'), 'Hackney')
            ->create(['ages' => [AgeBand::Primary, AgeBand::Secondary]]);

        $this->get('/resources')
            ->assertSee('<span class="badge badge--paid">Paid</span>', false)
            ->assertSee('href="'.route('browse.category', 'maths').'"', false)
            ->assertSee('Ages 5–16')
            ->assertSee('Hackney, London');
    }

    public function test_the_list_paginates_and_keeps_the_filters_in_the_links(): void
    {
        Resource::factory()->count(BrowseController::PER_PAGE + 1)->create(['tags' => ['Keep']]);

        $this->get('/resources?q=keep')
            ->assertSee('Page 1 of 2')
            ->assertSee('resources?q=keep&amp;page=2', false);

        $this->get('/resources?page=2')
            ->assertSee('<link rel="canonical" href="https://homeedresource.co.uk/resources?page=2">', false);
    }
}
