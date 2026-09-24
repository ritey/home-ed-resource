<?php

namespace App\Models;

use App\Enums\CategoryGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'group', 'blurb', 'position'])]
class Category extends Model
{
    /**
     * Set by Directory::categories(): resources in this category, primary
     * or secondary.
     */
    public int $total = 0;

    protected function casts(): array
    {
        return [
            'group' => CategoryGroup::class,
        ];
    }

    /** Resources whose primary category this is. */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
