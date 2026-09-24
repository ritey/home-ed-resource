<?php

namespace App\Support;

use App\Enums\Tier;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The list as the pages see it: every resource in order, grouped by tier,
 * and the "updated" date, which is the most recent last_checked (per the
 * handoff, never hand-written).
 */
class Directory
{
    /** @var Collection<int, resource> */
    public readonly Collection $resources;

    public function __construct()
    {
        $this->resources = Resource::ordered()->get();
    }

    /**
     * Every tier in display order, including empty ones -- an empty tier
     * still renders, with its written explanation.
     *
     * @return Collection<int, array{tier: Tier, resources: Collection<int, resource>}>
     */
    public function tiers(): Collection
    {
        return collect(Tier::cases())->map(fn (Tier $tier) => [
            'tier' => $tier,
            'resources' => $this->resources->where('tier', $tier)->values(),
        ]);
    }

    public function lastUpdated(): ?Carbon
    {
        return $this->resources->max('last_checked');
    }

    /**
     * The canonical base URL with a trailing slash. Taken from APP_URL, not
     * the request, so a www/apex mix-up can't leak into canonical tags.
     */
    public static function baseUrl(): string
    {
        return rtrim(config('app.url'), '/').'/';
    }
}
