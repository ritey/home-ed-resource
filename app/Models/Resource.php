<?php

namespace App\Models;

use App\Enums\Tier;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One website on the list.
 *
 * `description` is the family's own verdict; `more` is an optional factual
 * synopsis, one string per paragraph, drawn from the site itself.
 */
#[Fillable([
    'title', 'url', 'domain', 'tier', 'description', 'more', 'tags',
    'last_checked', 'image', 'image_alt', 'position',
])]
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tier' => Tier::class,
            'more' => 'array',
            'tags' => 'array',
            'last_checked' => 'date',
        ];
    }

    /**
     * List order: as entered, oldest first on a tie.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
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
