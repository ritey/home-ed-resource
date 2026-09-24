<?php

namespace App\Http\Controllers;

use App\Support\Directory;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(Directory $directory): View
    {
        $email = config('site.email');

        return view('home', [
            'directory' => $directory,
            'tiers' => $directory->tiers(),
            'updated' => $directory->lastUpdated(),
            'jsonLd' => StructuredData::for($directory),
            // Split so the address never appears whole in the served markup.
            'emailUser' => str($email)->before('@'),
            'emailDomain' => str($email)->after('@'),
            'emailReversed' => strrev($email),
        ]);
    }
}
