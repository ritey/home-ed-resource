# Home Ed Resource

A single-page, hand-curated directory of UK home education resources. Design 2a —
warm paper tones, Bricolage Grotesque headings, rounded pills, a screenshot beside
every entry, and a visible "we last looked" date.

## Build

`index.html` is **generated** — do not edit it directly, your changes will be
overwritten. Entry counts and the "Updated …" pill are derived from the data, so the
page is built from `resources.json`:

```sh
python3 build.py          # writes index.html, llms.txt, sitemap.xml
python3 shot.py           # captures thumbnails for entries that lack one
./deploy.sh               # builds, then rsyncs to DEPLOY_HOST
```

`build.py` needs nothing but Python 3.8+. `shot.py` additionally needs Chrome,
ImageMagick and `pip install websocket-client`.

## Files

| File | Purpose |
| --- | --- |
| `resources.json` | **Source of truth.** Site copy, tiers, and every resource |
| `build.py` | Generates `index.html`, `llms.txt`, `sitemap.xml` |
| `shot.py` | Captures entry thumbnails into `images/` |
| `styles.css` | All styling; design tokens as custom properties at the top |
| `fonts/` | Self-hosted Bricolage Grotesque + Karla (woff2, ~154 KB) |
| `images/` | Stored per-resource screenshots, 532×280 |
| `index.html`, `llms.txt`, `sitemap.xml` | Generated |
| `robots.txt` | Crawl rules; explicitly allows AI/answer-engine crawlers |
| `site.webmanifest`, `og-image.png`, `favicon.*` | Metadata and imagery |
| `design_handoff_home_ed_resource/` | Design reference (2a); not deployed |

## Adding a resource

Add an object to `resources` in `resources.json`, then run `python3 shot.py` and
`python3 build.py`.

```json
{
  "title": "Name",
  "url": "https://example.com",
  "domain": "example.com",
  "tier": "free",
  "description": "What it is and who it suited, in your own words.",
  "tags": ["Teens", "GCSE"],
  "lastChecked": "2026-09-18"
}
```

Everything else follows: the entry markup, the "n things so far" and "Updated …" pills,
the JSON-LD `ItemList`, `llms.txt` and the sitemap `lastmod`. "Updated" is the most
recent `lastChecked` across all entries, per the handoff.

The build refuses to run on a missing field, an unknown `tier`, a duplicate URL or a bad
date, so a typo fails loudly rather than shipping.

### Thumbnails

`shot.py` drives headless Chrome over the DevTools protocol: it loads the site, clicks
through the usual cookie/consent dialogs (and hides fixed overlays if it can't find a
button), then writes a 532×280 PNG — 2× the 266×140 slot — into `images/` and records
the path on the resource.

```sh
python3 shot.py                  # only entries with no image yet
python3 shot.py idea.org.uk      # one domain
python3 shot.py --all            # re-shoot everything
```

**Always look at the result before committing.** Consent dialogs vary; a banner that
survived will be sitting in the thumbnail. Both current entries were captured this way
and checked.

If a capture fails, the entry simply keeps no `image` and the page renders the tinted
fallback tile with the domain name — so a failure degrades rather than breaks. You can
also drop a hand-made 532×280 PNG into `images/` and point `image` at it.

## Design notes

Tokens are at the top of `styles.css`. The `oklch()` accents carry sRGB fallbacks
(`#c56a3e`, `#a04f27`, `#ddae6c`) restored to `oklch()` under `@supports`.

Deliberate, per the handoff — don't "fix" these:

- Rounded corners only on pills (100px), thumbnails (13–14px) and the header dot (50%).
  Nothing else is rounded. No shadows, no gradients.
- `--muted: #5c5349` and `--muted-2: #6b6157` are at the AA limit. Do not lighten.
- The newsletter is one footer line, not a section. When a provider is connected,
  "Yes please" should open its hosted form or reveal a small inline field.
- Entry titles are the link; there is no "read more". Tag pills are plain text, not
  buttons.
- No JS is required to render. The only script assembles the contact email.

**Info-block headings are `<h2>`, styled at the specified 23px.** The handoff calls them
H3 visually, but an `<h3>` there would nest them under "Paid". Size per spec; tag chosen
for the document outline.

Thumbnails are decorative — the title beside them is the real link — so the image has
`alt=""` and the wrapping link is `aria-hidden="true" tabindex="-1"`, keeping tab order
to one stop per entry.

### Email obfuscation

The address is split across `data-` attributes and assembled by a small inline script, so
it appears nowhere in the served markup (verified). Without JS the visible text still
reads correctly (reversed in source, flipped by CSS) and the link falls back to the
`#suggest` anchor.

For consistency the plaintext address is also kept out of the JSON-LD and `llms.txt` —
schema uses a `contactPoint` URL instead. If you would rather be reachable by machines,
put `"email"` back on the `Organization` node in `build.py`.

## SEO

In place: canonical, `robots` with `max-snippet:-1`, full Open Graph and Twitter
`summary_large_image`, JSON-LD (`Organization`, `WebSite`, `CollectionPage`, `ItemList`
with each resource described and its tags as `keywords`), `robots.txt` naming AI
crawlers, `sitemap.xml`, `llms.txt`, one `<h1>`, `lang="en-GB"`, skip link.

Fonts are self-hosted with `font-display: swap` and preloaded; thumbnails are `lazy` with
explicit `width`/`height` so they cost no layout shift. There is no render-blocking
third-party JS.

### Before publishing

- [ ] **Pick one canonical hostname** — apex or `www`, then 301-redirect the other.
      It is set once in `resources.json` (`site.url`) and flows everywhere from there;
      `robots.txt` has it hardcoded.
- [ ] Serve over HTTPS with HSTS
- [ ] Decide the newsletter provider and point "Yes please" at it
- [ ] Verify in Google Search Console and Bing Webmaster Tools, submit the sitemap
- [ ] Test the share card: opengraph.xyz, LinkedIn Post Inspector, Facebook Debugger
- [ ] Validate schema at validator.schema.org
- [ ] Serve `fonts/*.woff2` and `images/*.png` with a long `Cache-Control`

### Further improvements

1. **Add depth.** Two entries is thin. Thirty well-described entries will move rankings
   more than any tag in the `<head>`, and the descriptions are what AI assistants quote.
2. **Then per-resource pages** (`/resources/idea/`), once there are enough to justify it.
3. **Get listed elsewhere** — UK home-ed forums and groups, local authority EHE pages.
4. **A `/disclaimer` page** if the short footer line ever needs the full legal text.
5. **Re-shoot thumbnails periodically** — sites redesign. `python3 shot.py --all` when
   you do a review pass.

## Deployment

`./deploy.sh` runs the build then rsyncs, excluding the build tools, the design handoff
and `resources.json`. Set `DEPLOY_HOST` (default is the placeholder `you@your-vps`).
Everything served is static.
