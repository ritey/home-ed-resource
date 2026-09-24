<?php

namespace App\Support;

use App\Enums\AgeBand;
use App\Enums\Tier;
use App\Models\Category;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The search and filters on /resources, read from the query string.
 *
 * Unknown or malformed values are dropped rather than rejected: this is a
 * GET form on cookie-free routes, so there is no session to redirect errors
 * back through, and a stale bookmark should still show something.
 */
final class BrowseFilters
{
    public function __construct(
        public readonly string $q = '',
        public readonly ?Category $category = null,
        public readonly ?Region $region = null,
        public readonly bool $online = false,
        public readonly ?AgeBand $age = null,
        public readonly ?Tier $cost = null,
    ) {}

    public static function fromRequest(Request $request, ?Category $category = null, ?Region $region = null): self
    {
        $param = fn (string $key): string => is_string($value = $request->query($key)) ? trim($value) : '';

        $where = $param('where');

        return new self(
            q: Str::limit(Str::squish($param('q')), 100, ''),
            category: $category ?? ($param('category') !== '' ? Category::firstWhere('slug', $param('category')) : null),
            region: $region ?? ($where !== '' && $where !== 'online' ? Region::firstWhere('slug', $where) : null),
            online: $region === null && $where === 'online',
            age: AgeBand::tryFrom($param('age')),
            cost: Tier::tryFrom($param('cost')),
        );
    }

    /**
     * @param  Builder<\App\Models\Resource>  $query
     * @return Builder<\App\Models\Resource>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->when($this->q !== '', fn (Builder $q) => $q->search($this->q))
            ->when($this->category, fn (Builder $q, Category $c) => $q->inCategory($c))
            ->when($this->region, fn (Builder $q, Region $r) => $q->inRegion($r))
            ->when($this->online, fn (Builder $q) => $q->online())
            ->when($this->age, fn (Builder $q, AgeBand $a) => $q->forAge($a))
            ->when($this->cost, fn (Builder $q, Tier $t) => $q->costing($t));
    }

    public function isFiltered(): bool
    {
        return $this->q !== '' || $this->category || $this->region || $this->online
            || $this->age || $this->cost;
    }

    /** The "where" select's current value. */
    public function where(): string
    {
        return $this->online ? 'online' : ($this->region?->slug ?? '');
    }

    /**
     * What's applied, in words, for the results line: "Maths · Ages 11–16".
     *
     * @return array<int, string>
     */
    public function summary(): array
    {
        return array_values(array_filter([
            $this->q !== '' ? "matching “{$this->q}”" : null,
            $this->category?->name,
            $this->age?->label(),
            $this->cost?->label(),
            $this->online ? 'Online or UK-wide' : null,
            $this->region ? 'In '.$this->region->phrase : null,
        ]));
    }
}
