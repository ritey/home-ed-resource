<?php

namespace App\Models;

use App\Enums\AgeBand;
use App\Enums\Tier;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * One website on the list.
 *
 * `description` is the family's own verdict; `more` is an optional factual
 * synopsis, one string per paragraph, drawn from the site itself.
 *
 * `category` is the primary category (where it's listed and labelled);
 * `categories` holds up to two secondary ones that make it findable
 * elsewhere. No category means "global". No region means online or UK-wide.
 */
#[Fillable([
    'title', 'url', 'domain', 'tier', 'category_id', 'region_id', 'location',
    'ages', 'spotlight', 'description', 'more', 'tags', 'last_checked',
    'image', 'image_alt', 'position',
])]
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tier' => Tier::class,
            'ages' => AsEnumCollection::of(AgeBand::class),
            'spotlight' => 'boolean',
            'more' => 'array',
            'tags' => 'array',
            'last_checked' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Secondary categories. */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * List order: as entered, oldest first on a tie.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    #[Scope]
    protected function spotlit(Builder $query): void
    {
        $query->where('spotlight', true);
    }

    /**
     * Every word must appear somewhere: title, domain, either description,
     * tags, location, or the category/region name.
     */
    #[Scope]
    protected function search(Builder $query, string $terms): void
    {
        $words = Str::of($terms)
            ->replace(['%', '_'], ' ')      // LIKE wildcards; nobody searches for them
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(8);

        foreach ($words as $word) {
            $like = "%{$word}%";

            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $like)
                ->orWhere('domain', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('more', 'like', $like)
                ->orWhere('tags', 'like', $like)
                ->orWhere('location', 'like', $like)
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like))
                ->orWhereHas('categories', fn (Builder $c) => $c->where('name', 'like', $like))
                ->orWhereHas('region', fn (Builder $r) => $r->where('name', 'like', $like)));
        }
    }

    /** Primary or secondary. */
    #[Scope]
    protected function inCategory(Builder $query, Category $category): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('category_id', $category->id)
            ->orWhereHas('categories', fn (Builder $c) => $c->whereKey($category->id)));
    }

    #[Scope]
    protected function inRegion(Builder $query, Region $region): void
    {
        $query->whereIn('region_id', $region->coveredIds());
    }

    /** Online or UK-wide: no region at all. */
    #[Scope]
    protected function online(Builder $query): void
    {
        $query->whereNull('region_id');
    }

    #[Scope]
    protected function forAge(Builder $query, AgeBand $band): void
    {
        $query->whereJsonContains('ages', $band->value);
    }

    #[Scope]
    protected function costing(Builder $query, Tier $tier): void
    {
        $query->where('tier', $tier);
    }

    public function ageSpan(): ?string
    {
        return AgeBand::span($this->ages);
    }

    /** "Bristol, South West", "Scotland", or null when online/UK-wide. */
    public function place(): ?string
    {
        return collect([$this->location, $this->region?->name])->filter()->join(', ') ?: null;
    }

    /**
     * A thumbnail with alt text is content; without, it is decoration and the
     * title beside it is the only link a keyboard or screen reader meets.
     */
    public function hasMeaningfulImage(): bool
    {
        return filled($this->image_alt);
    }
}
