<?php

/*
|--------------------------------------------------------------------------
| Site copy
|--------------------------------------------------------------------------
|
| Everything on the page that is not a resource. The resources themselves
| are in the database (seeded from database/seeders/data/resources.json).
| The canonical URL comes from APP_URL, so set that to the one public
| hostname in production.
|
*/

return [

    'name' => 'Home Ed Resource',

    'email' => env('SITE_EMAIL', 'hello@homeedresource.co.uk'),

    'title' => 'Home Education (Ed) Resources in one place',

    // og:image:alt and twitter:image:alt.
    'image_alt' => 'Home Education (Ed) Resources in one place',

    'description' => 'A list of home education websites that a UK home ed family have actually used, with a note on who each one suited and when we last checked it. Free things first, paid things underneath.',

    // og:description and twitter:description.
    'share_description' => 'Home education websites and resources that are free or paid.',

    // The Organization node in the JSON-LD.
    'organization_description' => 'A list of home education websites with a note on who each one suited and when we last checked it. Free things first, paid things underneath.',

    'meta_description' => 'Home education websites and resources that are free or paid.',

    'standing_pill' => 'No ads, no affiliate links',

    // Keyed by App\Enums\Tier value.
    'tiers' => [
        'free' => [
            'label' => 'Free',
            'aside' => 'genuinely free, not a trial that turns into a bill',
            'empty' => 'Nothing free on the list at the moment.',
        ],
        'paid' => [
            'label' => 'Paid',
            'aside' => "nothing here yet, and that's on purpose",
            'empty' => "We're trying a couple of paid things at the moment. We'd rather use them for a term before telling you to spend money on them, so this bit stays empty for now.",
        ],
    ],

];
