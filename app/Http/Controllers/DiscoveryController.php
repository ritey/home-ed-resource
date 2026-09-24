<?php

namespace App\Http\Controllers;

use App\Support\Directory;
use Illuminate\Http\Response;

/**
 * The machine-facing files: llms.txt, sitemap.xml and robots.txt. Built from
 * the same data and APP_URL as the page, so none of them can drift from it.
 */
class DiscoveryController extends Controller
{
    public function llms(Directory $directory): Response
    {
        return response()
            ->view('discovery.llms', [
                'tiers' => $directory->tiers(),
                'total' => $directory->resources->count(),
                'updated' => $directory->lastUpdated(),
                'base' => Directory::baseUrl(),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(Directory $directory): Response
    {
        return response()
            ->view('discovery.sitemap', [
                'updated' => $directory->lastUpdated(),
                'base' => Directory::baseUrl(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response()
            ->view('discovery.robots', ['base' => Directory::baseUrl()])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
