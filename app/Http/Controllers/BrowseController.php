<?php

namespace App\Http\Controllers;

use App\Enums\AgeBand;
use App\Enums\Tier;
use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use App\Support\BrowseFilters;
use App\Support\Directory;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The full list with search and filters (/resources), plus a landing page
 * per category (/resources/maths) and region (/resources/near/london).
 * The landing pages are the same list with one filter fixed; they exist
 * as real URLs so they can be linked to and indexed.
 */
class BrowseController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $filters = BrowseFilters::fromRequest($request);

        return $this->render($filters, [
            'heading' => 'The full list',
            'lede' => 'Everything we’ve written up so far. Search it, or narrow it down by subject, age, cost or where you are.',
            'title' => 'All home education resources | '.config('site.name'),
            'description' => 'Every home education resource on the list, searchable and filterable by subject, age, cost and region.',
            'url' => Directory::url('resources'),
            'crumbs' => [],
            // Filtered and searched views are for people, not the index: they
            // point back to the plain list so crawlers don't chase every
            // combination of filters.
            'indexable' => ! $filters->isFiltered(),
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        $url = Directory::url("resources/{$category->slug}");

        return $this->render(BrowseFilters::fromRequest($request, category: $category), [
            'heading' => $category->name,
            'lede' => $category->blurb,
            'title' => "{$category->name} resources for home education | ".config('site.name'),
            'description' => $category->blurb,
            'url' => $url,
            'crumbs' => [['name' => $category->name, 'url' => $url]],
            'indexable' => true,
        ]);
    }

    public function region(Request $request, Region $region): View
    {
        $url = Directory::url("resources/near/{$region->slug}");
        $covers = $region->parent
            ? "Groups, places and exam centres for home ed families in {$region->phrase}, plus anything that covers the whole of {$region->parent->phrase}."
            : "Groups, places and exam centres for home ed families in {$region->phrase}.";

        return $this->render(BrowseFilters::fromRequest($request, region: $region), [
            'heading' => "Home ed in {$region->phrase}",
            'lede' => $covers,
            'title' => "Home education resources in {$region->phrase} | ".config('site.name'),
            'description' => $covers,
            'url' => $url,
            'crumbs' => [['name' => $region->name, 'url' => $url]],
            'indexable' => true,
        ]);
    }

    private function render(BrowseFilters $filters, array $page): View
    {
        $results = $filters->apply(Resource::query())
            ->with(['category', 'region'])
            ->ordered()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $shown = $results->getCollection();

        return view('browse', [
            ...$page,
            'filters' => $filters,
            'results' => $results,
            'categories' => Category::ordered()->get(),
            'nations' => Region::ordered()->whereNull('parent_id')->with(['children' => fn ($q) => $q->ordered()])->get(),
            'ages' => AgeBand::cases(),
            'tiers' => Tier::cases(),
            // The plain list is canonical for every page of itself.
            'canonical' => $page['indexable'] && $results->currentPage() > 1
                ? $page['url'].'?page='.$results->currentPage()
                : $page['url'],
            'jsonLd' => StructuredData::listing(
                $page['url'],
                $page['heading'],
                $page['description'],
                $shown,
                $shown->max('last_checked'),
                [['name' => 'The full list', 'url' => Directory::url('resources')], ...$page['crumbs']],
            ),
        ]);
    }
}
