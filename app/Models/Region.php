<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A nation (England, Scotland, Wales, Northern Ireland) or one of England's
 * regions. A resource with no region is online or UK-wide.
 */
#[Fillable(['slug', 'name', 'phrase', 'parent_id', 'position'])]
class Region extends Model
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * The region ids a "where" filter on this region should match.
     *
     * A nation with regions matches itself and all of them: "England" finds
     * everything English. A region matches itself and its nation: "South
     * West" also finds things that cover all of England.
     *
     * @return array<int, int>
     */
    public function coveredIds(): array
    {
        $children = $this->children()->pluck('id')->all();

        return $children
            ? [$this->id, ...$children]
            : array_values(array_filter([$this->id, $this->parent_id]));
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
