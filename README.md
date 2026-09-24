# Home Ed Resource

A hand-curated directory of UK home education resources, as a Laravel app: a homepage
with a spotlight, and a searchable, filterable list by subject, age, cost and region. Design 2a from `old/design_handoff_home_ed_resource/`: warm paper tones, Bricolage
Grotesque headings, rounded pills, a screenshot beside every entry, and a visible "we
last looked" date.

The previous static-site version (Python `build.py` generating `index.html`) is kept in
[`old/`](old/) for reference while this settles in. Nothing in the app reads from it.

## Setup

Needs PHP 8.3+, Composer, and Node 22 (`.nvmrc`; Vite 8 will not run on older Node).

```sh
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
nvm use && npm install && npm run build
php artisan serve
```

`php artisan test` runs the suite (SQLite in memory, no Vite build needed).

## Where things live

| To change | Edit |
| --- | --- |
| A resource (add, edit, reorder, spotlight) | `database/seeders/data/resources.json`, then `php artisan db:seed` |
| Categories (names, blurbs, order) | `database/seeders/data/categories.json` |
| Regions | `database/seeders/data/regions.json` |
| Page title, meta/share descriptions, pill copy, cost labels | `config/site.php` |
| Canonical hostname (canonical tag, OG URLs, JSON-LD, sitemap, robots) | `APP_URL` in `.env` |
| Contact address | `SITE_EMAIL` in `.env` |
| Page markup | `resources/views/home.blade.php`, `browse.blade.php`, `components/` (entry, filters, search, category-tiles, footer) |
| What the filters and search do | `app/Support/BrowseFilters.php`, scopes on `app/Models/Resource.php` |
| `<head>`, header | `resources/views/components/layouts/site.blade.php` |
| JSON-LD | `app/Support/StructuredData.php` |
| `llms.txt`, `sitemap.xml`, `robots.txt` | `resources/views/discovery/` (served by routes, not static files) |
| Styles | `resources/css/app.css` (tokens at the top) |
| Fonts | `resources/fonts/` (self-hosted woff2, hashed by Vite) |
| Thumbnails | `public/images/`, 532×280 PNG, referenced by `image` on the resource |

## Resources

The list is a `resources` table (`App\Models\Resource`), seeded from
`database/seeders/data/resources.json` after the categories and regions
(`php artisan db:seed` runs both, in order). The seeders are safe to re-run: rows are
matched on `url` or `slug` and updated in place, and order follows the files. They refuse
anything malformed (unknown tier, category, region or age band, a bad date, more than two
secondary categories, a secondary repeating the primary), so a typo fails loudly.

```json
{
    "title": "Name",
    "url": "https://example.com",
    "domain": "example.com",
    "tier": "free",
    "category": "maths",
    "also": ["exams"],
    "region": null,
    "location": null,
    "ages": ["primary", "secondary"],
    "spotlight": false,
    "description": "What it is and who it suited, in your own words.",
    "more": ["Optional. Longer synopsis, one string per paragraph."],
    "tags": ["Teens", "GCSE"],
    "last_checked": "2026-09-18",
    "image": "images/example-com.png",
    "image_alt": "Optional. See below."
}
```

The page derives the rest: the "n things so far" and "Updated …" pills, the JSON-LD
`ItemList`, `llms.txt` and the sitemap `lastmod`. "Updated" is the most recent
`last_checked` across all entries, per the handoff.

### Categories, regions and ages

- **`category`**: the primary category, as a slug from `categories.json`. It decides the
  entry's label and link. `null` means "global": it's allowed, but most things should
  sit somewhere. 13 categories in two groups: nine subjects, plus four practical ones
  (life skills, exams, groups and meetups, support and advice).
- **`also`**: up to two secondary categories. The resource shows up when someone
  filters by those too, and counts towards their tiles.
- **`region`**: a slug from `regions.json`, or `null` for online or UK-wide (most
  things). Nations are the top level, and England has its nine regions beneath it.
  Filtering by a region also shows its nation-wide resources ("South West" includes
  "all of England"). Filtering by a nation includes all its regions. Online resources
  appear in a region only when "Anywhere" is chosen.
- **`location`**: optional free text ("Bristol"), shown on the entry and searchable.
- **`ages`**: any of `early-years` (0–5), `primary` (5–11), `secondary` (11–16),
  `16-plus`. These are age bands rather than key stages, which Scotland doesn't use.
  The entry shows the overall span ("Ages 5–16"). The freeform `tags` stay for the
  voice.
- **`spotlight`**: shown under "Worth a look" on the homepage, up to six, in list order.
  If nothing is flagged, the six most recently checked stand in, so the spotlight is
  never empty.

**`description` versus `more`.** `description` is the family's own verdict ("the plan is
the bit we found useful"). `more` is a factual synopsis of what the site offers, drawn
from its own homepage. Keep opinion in the first and fact in the second, and attribute
a site's own marketing numbers ("the site reports …") rather than asserting them. `more`
is rendered on the page and in `llms.txt`, but not in the JSON-LD, so the schema
`description` stays short.

**Thumbnail alt text.** With no `image_alt`, the thumbnail is decorative (`alt=""`, and
the wrapping link is `aria-hidden="true" tabindex="-1"`, one tab stop per entry). With
`image_alt`, it is content, so the wrapper is *not* hidden. An `aria-hidden` ancestor
would stop a screen reader announcing the alt.

**New thumbnails.** `old/shot.py` still works for capturing them (headless Chrome,
clicks through consent banners, writes 532×280 PNGs). Copy the result into
`public/images/`, and look at it before committing, in case a banner survived.

## Pages

| URL | What |
| --- | --- |
| `/` | Intro and search, spotlight, category tiles (populated ones only; empty ones named in a sentence), regions in use |
| `/resources` | Everything, with search and filters (`q`, `category`, `age`, `cost`, `where`), 20 per page |
| `/resources/{category}` | Landing page: the list filtered to one category, with its blurb as the intro |
| `/resources/near/{region}` | Landing page for a nation or region |

Search is a plain query: every word must appear somewhere in the title, domain,
description, synopsis, tags, location, or category or region name. That's fine into the
hundreds; after that, move to SQLite FTS5 or Laravel Scout.

Filtered and searched views of `/resources` are `noindex, follow`, with their canonical
set to the plain list, so crawlers don't chase every combination. The landing pages are
indexable, and appear in the sitemap once they have something on them. Unknown filter
values are ignored rather than rejected: there's no session to show errors through, and
an old bookmark should still show something.

## Deliberate choices

- **No cookies.** The public routes skip the session, cookie and CSRF middleware
  (`routes/web.php`), so no response sets a cookie. That keeps the site banner-free, as
  the handoff intends, and lets a CDN cache it. Anything that later takes input (a
  suggestion form, the newsletter) needs its own routes *with* the `web` middleware.
- **Search and filters are one GET form.** No JS, so every result has a URL you can
  share or bookmark, and the pages stay cookie-free.
- **No JS required to render.** `resources/js/app.js` only assembles the contact
  `mailto:` so the address is never whole in the markup, and registers the fonts with
  Vite. Without JS the reversed text still reads correctly.
- **Hand-written `@font-face`, not the Vite plugin's `fonts` option.** The fonts are
  variable (`font-weight: 400 600`) and split by `unicode-range`. The plugin's `local()`
  provider takes one weight per file, so it would change how they load.
- **Info-block headings are `<h2>`**, styled at 23px. The handoff calls them H3 visually,
  but an `<h3>` would nest them under "Paid".
- Rounded corners only on pills, thumbnails and the header dot; no shadows, no gradients.
  `--muted` and `--muted-2` are at the AA limit, so do not lighten them.
- The newsletter is one footer line, not a section.

## Before publishing

- [ ] Set `APP_URL` to the one canonical hostname and 301 the other (apex vs `www`)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, HTTPS with HSTS
- [ ] Long `Cache-Control` on `/build/assets/*` (content-hashed) and `/images/*`
- [ ] Decide the newsletter provider and point "Yes please" at it
- [ ] Verify in Google Search Console and Bing Webmaster Tools, submit the sitemap
- [ ] Test the share card and validate the schema (validator.schema.org)

## Known audit warnings

**Word count.** About 870 words on the homepage with two entries. The spotlight shows the
`more` synopses (`<x-entry>` has a `full` prop to turn them off once six spotlit entries
make the page too long). More entries is the real fix. Padding prose would bring back the
over-polished voice the handoff removed.

**Title/H1 words not in body.** Re-check after any copy change. The old README has the
reasoning for why this is judged by accuracy, not keyword overlap.
