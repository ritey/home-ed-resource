<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use App\Support\Directory;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * The machine-facing files: llms.txt, sitemap.xml and robots.txt. Built from
 * the same data and APP_URL as the pages, so none of them can drift.
 */
class DiscoveryController extends Controller
{
    public function llms(Directory $directory): Response
    {
        $all = $directory->all();

        // Grouped by primary category, in category order; uncategorised last.
        $sections = Category::ordered()->get()
            ->map(fn (Category $c) => ['heading' => $c->name, 'blurb' => $c->blurb, 'url' => Directory::url("resources/{$c->slug}"), 'resources' => $all->where('category_id', $c->id)])
            ->push(['heading' => 'Everything else', 'blurb' => null, 'url' => null, 'resources' => $all->whereNull('category_id')])
            ->filter(fn (array $s) => $s['resources']->isNotEmpty());

        return response()
            ->view('discovery.llms', [
                'sections' => $sections,
                'total' => $all->count(),
                'updated' => $directory->lastUpdated(),
                'base' => Directory::baseUrl(),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(Directory $directory): Response
    {
        $all = $directory->all();
        $latest = fn (Collection $rs) => $rs->max('last_checked')?->toDateString();

        $urls = collect([
            ['loc' => Directory::baseUrl(), 'lastmod' => $latest($all), 'priority' => '1.0', 'image' => true],
            ['loc' => Directory::url('resources'), 'lastmod' => $latest($all), 'priority' => '0.8'],
        ]);

        // Landing pages only once they have something on them.
        foreach (Category::ordered()->get() as $category) {
            $in = $all->filter(fn (Resource $r) => $r->category_id === $category->id
                || $r->categories->contains($category));
            if ($in->isNotEmpty()) {
                $urls->push(['loc' => Directory::url("resources/{$category->slug}"), 'lastmod' => $latest($in), 'priority' => '0.7']);
            }
        }

        foreach (Region::ordered()->get() as $region) {
            $in = $all->whereIn('region_id', $region->coveredIds());
            if ($in->isNotEmpty()) {
                $urls->push(['loc' => Directory::url("resources/near/{$region->slug}"), 'lastmod' => $latest($in), 'priority' => '0.6']);
            }
        }

        return response()
            ->view('discovery.sitemap', ['urls' => $urls, 'base' => Directory::baseUrl()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response()
            ->view('discovery.robots', ['base' => Directory::baseUrl()])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
