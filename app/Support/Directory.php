<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Region;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The list as a whole: totals, the "updated" date (the most recent
 * last_checked, per the handoff, never hand-written), the spotlight, and
 * how many resources sit in each category and region.
 */
class Directory
{
    public const SPOTLIGHT_LIMIT = 6;

    public function total(): int
    {
        return Resource::count();
    }

    public function lastUpdated(): ?Carbon
    {
        $latest = Resource::max('last_checked');

        return $latest ? Carbon::parse($latest) : null;
    }

    /**
     * The flagged resources, or -- if none are flagged -- the most recently
     * checked, so the homepage is never left with an empty spotlight.
     *
     * @return Collection<int, \App\Models\Resource>
     */
    public function spotlight(): Collection
    {
        $query = Resource::with(['category', 'region'])->limit(self::SPOTLIGHT_LIMIT);

        $flagged = (clone $query)->spotlit()->ordered()->get();

        return $flagged->isNotEmpty()
            ? $flagged
            : $query->orderByDesc('last_checked')->ordered()->get();
    }

    /**
     * Every category in order, each with `total` set: resources in it as
     * primary or secondary, each counted once.
     *
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        $pairs = DB::table('resources')->whereNotNull('category_id')->select('id', 'category_id')
            ->union(DB::table('category_resource')->select('resource_id', 'category_id'));

        $totals = DB::query()->fromSub($pairs, 'pairs')
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return Category::ordered()->get()
            ->each(fn (Category $c) => $c->total = (int) ($totals[$c->id] ?? 0));
    }

    /**
     * Regions that have something in them, in order, with resources_count.
     *
     * @return Collection<int, Region>
     */
    public function regionsInUse(): Collection
    {
        return Region::ordered()->withCount('resources')->get()
            ->where('resources_count', '>', 0)
            ->values();
    }

    /**
     * Everything, for llms.txt and the sitemap.
     *
     * @return Collection<int, \App\Models\Resource>
     */
    public function all(): Collection
    {
        return Resource::with(['category', 'categories', 'region'])->ordered()->get();
    }

    /**
     * The canonical base URL with a trailing slash. Taken from APP_URL, not
     * the request, so a www/apex mix-up can't leak into canonical tags.
     */
    public static function baseUrl(): string
    {
        return rtrim(config('app.url'), '/').'/';
    }

    /** An absolute canonical URL for a path on this site. */
    public static function url(string $path = ''): string
    {
        return self::baseUrl().ltrim($path, '/');
    }
}
