<?php

namespace App\Support;

use App\Enums\Tier;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * JSON-LD. The homepage carries the Organization and WebSite nodes; every
 * listing page is a CollectionPage with an ItemList of what it shows, and
 * refers back to those nodes by @id.
 */
class StructuredData
{
    /**
     * @param  Collection<int, \App\Models\Resource>  $spotlight
     */
    public static function home(Collection $spotlight, ?Carbon $updated): array
    {
        $base = Directory::baseUrl();
        $site = config('site');

        return self::document([
            [
                '@type' => 'Organization',
                '@id' => $base.'#organization',
                'name' => $site['name'],
                'url' => $base,
                'description' => $site['organization_description'],
                'areaServed' => ['@type' => 'Country', 'name' => 'United Kingdom'],
                // A URL rather than the address, to keep it out of the markup.
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'contactType' => 'editorial',
                    'url' => $base.'#suggest',
                ],
                'knowsAbout' => [
                    'home education', 'elective home education', 'home schooling',
                    'GCSE revision', 'digital skills', 'curriculum resources',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $base.'#website',
                'url' => $base,
                'name' => $site['name'],
                'description' => $site['description'],
                'publisher' => ['@id' => $base.'#organization'],
                'inLanguage' => 'en-GB',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => Directory::url('resources').'?q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            ...self::page($base, $site['title'], $site['description'], $spotlight, $updated, [
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $base.'og-image.png',
                    'width' => 1200,
                    'height' => 630,
                ],
            ]),
        ]);
    }

    /**
     * @param  Collection<int, \App\Models\Resource>  $resources  what this page shows
     * @param  array<int, array{name: string, url: string}>  $crumbs  after "Home"
     */
    public static function listing(string $url, string $name, string $description, Collection $resources, ?Carbon $updated, array $crumbs = []): array
    {
        $graph = self::page($url, $name, $description, $resources, $updated);

        if ($crumbs) {
            $trail = [['name' => config('site.name'), 'url' => Directory::baseUrl()], ...$crumbs];

            $graph[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($trail)->values()->map(fn (array $c, int $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $c['name'],
                    'item' => $c['url'],
                ])->all(),
            ];
        }

        return self::document($graph);
    }

    private static function page(string $url, string $name, string $description, Collection $resources, ?Carbon $updated, array $extra = []): array
    {
        $base = Directory::baseUrl();

        $page = array_filter([
            '@type' => 'CollectionPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'isPartOf' => ['@id' => $base.'#website'],
            'about' => ['@id' => $base.'#organization'],
            'inLanguage' => 'en-GB',
            'dateModified' => $updated?->toDateString(),
            ...$extra,
            'mainEntity' => ['@id' => $url.'#resource-list'],
        ], fn ($v) => $v !== null);

        $items = $resources->values()->map(fn (Resource $r, int $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => array_filter([
                '@type' => 'WebSite',
                '@id' => rtrim($r->url, '/').'/#website',
                'name' => $r->title,
                'url' => $r->url,
                'description' => $r->description,
                'inLanguage' => 'en-GB',
                'isAccessibleForFree' => $r->tier === Tier::Free,
                'dateModified' => $r->last_checked->toDateString(),
                'keywords' => $r->tags,
                'genre' => $r->category?->name,
                'spatialCoverage' => $r->place(),
            ], fn ($v) => $v !== null),
        ])->all();

        return [
            $page,
            [
                '@type' => 'ItemList',
                '@id' => $url.'#resource-list',
                'name' => $name,
                'numberOfItems' => count($items),
                'itemListOrder' => 'https://schema.org/ItemListUnordered',
                'itemListElement' => $items,
            ],
        ];
    }

    private static function document(array $graph): array
    {
        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
