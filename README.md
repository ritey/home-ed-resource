# Home Ed Resource

A single-page, hand-curated directory of UK home education resources, as a Laravel
app. Design 2a from `old/design_handoff_home_ed_resource/`: warm paper tones, Bricolage
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
| A resource (add, edit, reorder) | `database/seeders/data/resources.json`, then `php artisan db:seed` |
| Page title, meta/share descriptions, pill copy, tier headings and asides | `config/site.php` |
| Canonical hostname (canonical tag, OG URLs, JSON-LD, sitemap, robots) | `APP_URL` in `.env` |
| Contact address | `SITE_EMAIL` in `.env` |
| Page markup | `resources/views/home.blade.php`, `components/entry.blade.php`, `components/tier-section.blade.php` |
| `<head>`, header | `resources/views/components/layouts/site.blade.php` |
| JSON-LD | `app/Support/StructuredData.php` |
| `llms.txt`, `sitemap.xml`, `robots.txt` | `resources/views/discovery/` (served by routes, not static files) |
| Styles | `resources/css/app.css` (tokens at the top) |
| Fonts | `resources/fonts/` (self-hosted woff2, hashed by Vite) |
| Thumbnails | `public/images/`, 532×280 PNG, referenced by `image` on the resource |

## Resources

The list is a `resources` table (`App\Models\Resource`), seeded from
`database/seeders/data/resources.json`. The seeder is safe to re-run: rows are matched
on `url` and updated in place, and list order follows the file. It refuses a missing
field, an unknown `tier`, a bad date or a malformed `more`, so a typo fails loudly.

```json
{
    "title": "Name",
    "url": "https://example.com",
    "domain": "example.com",
    "tier": "free",
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

## Deliberate choices

- **No cookies.** The public routes skip the session, cookie and CSRF middleware
  (`routes/web.php`), so no response sets a cookie. That keeps the site banner-free, as
  the handoff intends, and lets a CDN cache it. Anything that later takes input (a
  suggestion form, the newsletter) needs its own routes *with* the `web` middleware.
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

**Word count.** About 770 words of body text with two entries, most of it from the `more`
synopses. More entries is the real fix. Padding prose would bring back the
over-polished voice the handoff removed.

**Title/H1 words not in body.** Re-check after any copy change. The old README has the
reasoning for why this is judged by accuracy, not keyword overlap.
