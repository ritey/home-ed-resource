<?php

namespace App\Http\Controllers;

use App\Enums\CategoryGroup;
use App\Support\Directory;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(Directory $directory): View
    {
        $email = config('site.email');
        $spotlight = $directory->spotlight();
        $updated = $directory->lastUpdated();
        $categories = $directory->categories();

        return view('home', [
            'total' => $directory->total(),
            'updated' => $updated,
            'spotlight' => $spotlight,
            // Tiles only for categories with something in them; the empty
            // ones are named in a sentence, so a young list doesn't look
            // like a page of "nothing yet" boxes.
            'groups' => collect(CategoryGroup::cases())
                ->map(fn (CategoryGroup $g) => [
                    'group' => $g,
                    'categories' => $categories->where('group', $g)->where('total', '>', 0)->values(),
                ])
                ->filter(fn (array $g) => $g['categories']->isNotEmpty())
                ->values(),
            'emptyCategories' => $categories->where('total', 0)->values(),
            'regions' => $directory->regionsInUse(),
            'jsonLd' => StructuredData::home($spotlight, $updated),
            // Split so the address never appears whole in the served markup.
            'emailUser' => str($email)->before('@'),
            'emailDomain' => str($email)->after('@'),
            'emailReversed' => strrev($email),
        ]);
    }
}
