<?php

namespace App\Support;

use App\Enums\Tier;
use App\Models\Resource;

/**
 * The JSON-LD graph for the home page: Organization, WebSite,
 * CollectionPage and an ItemList with one entry per resource.
 */
class StructuredData
{
    public static function for(Directory $directory): array
    {
        $base = Directory::baseUrl();
        $site = config('site');

        $items = $directory->resources->values()->map(fn (Resource $r, int $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => [
                '@type' => 'WebSite',
                '@id' => rtrim($r->url, '/').'/#website',
                'name' => $r->title,
                'url' => $r->url,
                'description' => $r->description,
                'inLanguage' => 'en-GB',
                'isAccessibleForFree' => $r->tier === Tier::Free,
                'dateModified' => $r->last_checked->toDateString(),
                'keywords' => $r->tags,
            ],
        ])->all();

        $page = [
            '@type' => 'CollectionPage',
            '@id' => $base.'#webpage',
            'url' => $base,
            'name' => $site['title'],
            'description' => $site['description'],
            'isPartOf' => ['@id' => $base.'#website'],
            'about' => ['@id' => $base.'#organization'],
            'inLanguage' => 'en-GB',
            'dateModified' => $directory->lastUpdated()?->toDateString(),
            'primaryImageOfPage' => [
                '@type' => 'ImageObject',
                'url' => $base.'og-image.png',
                'width' => 1200,
                'height' => 630,
            ],
            'mainEntity' => ['@id' => $base.'#resource-list'],
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
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
                ],
                array_filter($page, fn ($v) => $v !== null),
                [
                    '@type' => 'ItemList',
                    '@id' => $base.'#resource-list',
                    'name' => 'Home education resources',
                    'description' => $site['description'],
                    'numberOfItems' => count($items),
                    'itemListOrder' => 'https://schema.org/ItemListUnordered',
                    'itemListElement' => $items,
                ],
            ],
        ];
    }
}
