#!/usr/bin/env python3
"""
Build index.html, llms.txt and sitemap.xml from resources.json.

The handoff requires entry counts and the "last updated" date to be derived
from the resource data rather than hand-written, so the page is generated
rather than edited directly. Edit resources.json, then run:

    python3 build.py

No dependencies. Python 3.8+.
"""

import html
import json
import re
import sys
from datetime import date, datetime
from pathlib import Path

ROOT = Path(__file__).parent
OUT = ROOT / "index.html"


# --- helpers ---------------------------------------------------------------

def esc(s):
    """Escape for HTML text/attribute context."""
    return html.escape(str(s), quote=True)


def parse_date(s):
    try:
        return datetime.strptime(s, "%Y-%m-%d").date()
    except ValueError:
        sys.exit(f"error: bad date {s!r} — expected YYYY-MM-DD")


def long_date(d):
    """22 September 2026"""
    return f"{d.day} {d.strftime('%B')} {d.year}"


def short_date(d):
    """18 Sep"""
    return f"{d.day} {d.strftime('%b')}"


# --- load + validate -------------------------------------------------------

def load():
    data = json.loads((ROOT / "resources.json").read_text(encoding="utf-8"))
    tier_ids = {t["id"] for t in data["tiers"]}
    seen = set()

    for r in data["resources"]:
        for field in ("title", "url", "domain", "tier", "description", "tags", "lastChecked"):
            if field not in r:
                sys.exit(f"error: resource {r.get('title', '?')!r} is missing {field!r}")
        if r["tier"] not in tier_ids:
            sys.exit(f"error: {r['title']!r} has unknown tier {r['tier']!r}")
        if r["url"] in seen:
            sys.exit(f"error: duplicate url {r['url']!r}")
        seen.add(r["url"])
        r["_checked"] = parse_date(r["lastChecked"])

    if not data["resources"]:
        sys.exit("error: no resources — the status line would read '0 entries'")
    return data


# --- fragments -------------------------------------------------------------

def render_entry(r):
    tags = "".join(f'\n              <li class="tag">{esc(t)}</li>' for t in r["tags"])

    if r.get("image"):
        # Decorative: the title beside it is the real link, so alt="" and the
        # wrapper is kept out of the tab order.
        thumb = (f'<a class="entry__thumb" href="{esc(r["url"])}" tabindex="-1" aria-hidden="true"\n'
                 f'               target="_blank" rel="noopener noreferrer nofollow">\n'
                 f'              <img src="{esc(r["image"])}" alt="" width="266" height="140"\n'
                 f'                   loading="lazy" decoding="async">\n'
                 f'            </a>')
    else:
        thumb = (f'<span class="entry__thumb entry__thumb--empty" aria-hidden="true">'
                 f'{esc(r["domain"])}</span>')

    return f"""          <article class="entry">
            <div class="entry__main">
              <div class="entry__head">
                <h3 class="entry__title">
                  <a href="{esc(r['url'])}" target="_blank" rel="noopener noreferrer nofollow">{esc(r['title'])}</a>
                </h3>
                <span class="entry__checked">we last looked <time datetime="{r['lastChecked']}">{short_date(r['_checked'])}</time></span>
              </div>
              <p class="entry__description">{esc(r['description'])}</p>
              <ul class="entry__tags">{tags}
                <li class="entry__domain">{esc(r['domain'])}</li>
              </ul>
            </div>
            {thumb}
          </article>"""


def render_section(tier, entries):
    head_mod = " section-head--paid" if tier["id"] != "free" else ""
    head = f"""    <div class="section-head{head_mod}">
      <h2 id="{esc(tier['id'])}-heading">{esc(tier['label'])}</h2>
      <span class="section-head__aside">&mdash; {esc(tier['aside'])}</span>
    </div>"""

    if entries:
        body = ('    <div class="entries">\n'
                + "\n".join(render_entry(r) for r in entries)
                + "\n    </div>")
    else:
        body = f'    <p class="section__empty">{esc(tier["empty"])}</p>'

    return (f'    <section aria-labelledby="{esc(tier["id"])}-heading" id="{esc(tier["id"])}">\n'
            f'{head}\n{body}\n    </section>')


def render_jsonld(data, updated, by_tier):
    site = data["site"]
    base = site["url"]
    items = []
    for i, r in enumerate(data["resources"], start=1):
        items.append({
            "@type": "ListItem",
            "position": i,
            "item": {
                "@type": "WebSite",
                "@id": r["url"].rstrip("/") + "/#website",
                "name": r["title"],
                "url": r["url"],
                "description": r["description"],
                "inLanguage": "en-GB",
                "isAccessibleForFree": r["tier"] == "free",
                "dateModified": r["lastChecked"],
                "keywords": r["tags"],
            },
        })

    graph = [
        {
            "@type": "Organization",
            "@id": base + "#organization",
            "name": site["name"],
            "url": base,
            "description": site["description"],
            "areaServed": {"@type": "Country", "name": "United Kingdom"},
            "contactPoint": {
                "@type": "ContactPoint",
                "contactType": "editorial",
                "url": base + "#suggest",
            },
            "knowsAbout": [
                "home education", "elective home education", "home schooling",
                "GCSE revision", "digital skills", "curriculum resources",
            ],
        },
        {
            "@type": "WebSite",
            "@id": base + "#website",
            "url": base,
            "name": site["name"],
            "description": site["description"],
            "publisher": {"@id": base + "#organization"},
            "inLanguage": "en-GB",
        },
        {
            "@type": "CollectionPage",
            "@id": base + "#webpage",
            "url": base,
            "name": site["title"],
            "description": site["description"],
            "isPartOf": {"@id": base + "#website"},
            "about": {"@id": base + "#organization"},
            "inLanguage": "en-GB",
            "dateModified": updated.isoformat(),
            "primaryImageOfPage": {
                "@type": "ImageObject",
                "url": base + "og-image.png",
                "width": 1200,
                "height": 630,
            },
            "mainEntity": {"@id": base + "#resource-list"},
        },
        {
            "@type": "ItemList",
            "@id": base + "#resource-list",
            "name": "Home education resources",
            "description": site["description"],
            "numberOfItems": len(items),
            "itemListOrder": "https://schema.org/ItemListUnordered",
            "itemListElement": items,
        },
    ]
    payload = {"@context": "https://schema.org", "@graph": graph}
    return json.dumps(payload, indent=2, ensure_ascii=False)


# --- page ------------------------------------------------------------------

def render_page(data):
    site = data["site"]
    base = site["url"]
    by_tier = {t["id"]: [r for r in data["resources"] if r["tier"] == t["id"]]
               for t in data["tiers"]}

    updated = max(r["_checked"] for r in data["resources"])
    total = len(data["resources"])
    # Pills 1 and 2 are derived from the data; pill 3 is standing copy.
    noun = "thing" if total == 1 else "things"
    pill_count = f"{total} {noun} so far"
    pill_updated = f"Updated {updated.day} {updated.strftime('%B')}"

    sections = "\n\n".join(render_section(t, by_tier[t["id"]]) for t in data["tiers"])
    jsonld = render_jsonld(data, updated, by_tier)

    user, domain = site["email"].split("@")

    page = TEMPLATE
    repl = {
        "@@TITLE@@": esc(site["title"]),
        "@@DESC@@": esc(site["description"]),
        "@@SHARE_DESC@@": esc(site["shareDescription"]),
        "@@BASE@@": esc(base),
        "@@NAME@@": esc(site["name"]),
        "@@PILL_COUNT@@": esc(pill_count),
        "@@PILL_UPDATED@@": esc(pill_updated),
        "@@PILL_STANDING@@": esc(site.get("standingPill", "")),
        "@@UPDATED_ISO@@": updated.isoformat(),
        "@@SECTIONS@@": sections,
        "@@JSONLD@@": jsonld,
        "@@EMAIL_USER@@": esc(user),
        "@@EMAIL_DOMAIN@@": esc(domain),
        "@@EMAIL_REVERSED@@": esc(site["email"][::-1]),
        "@@YEAR@@": str(updated.year),
    }
    for k, v in repl.items():
        page = page.replace(k, v)

    leftover = re.findall(r"@@[A-Z_]+@@", page)
    if leftover:
        sys.exit(f"error: unreplaced tokens {set(leftover)}")
    return page, updated, total


TEMPLATE = r"""<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>@@TITLE@@</title>
<meta name="description" content="@@DESC@@">
<link rel="canonical" href="@@BASE@@">
<meta name="theme-color" content="#fdf3e4">
<meta name="color-scheme" content="light">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

<!-- Open Graph -->
<meta property="og:site_name" content="@@NAME@@">
<meta property="og:locale" content="en_GB">
<meta property="og:type" content="website">
<meta property="og:url" content="@@BASE@@">
<meta property="og:title" content="@@TITLE@@">
<meta property="og:description" content="@@SHARE_DESC@@">
<meta property="og:image" content="@@BASE@@og-image.png">
<meta property="og:image:secure_url" content="@@BASE@@og-image.png">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Home Ed Resource — good stuff for home ed, all in one place.">

<!-- Twitter / X -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@@TITLE@@">
<meta name="twitter:description" content="@@SHARE_DESC@@">
<meta name="twitter:image" content="@@BASE@@og-image.png">
<meta name="twitter:image:alt" content="Home Ed Resource — good stuff for home ed, all in one place.">

<!-- Icons -->
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

<link rel="preload" href="fonts/bricolage-grotesque-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="fonts/karla-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="styles.css">

<script type="application/ld+json">
@@JSONLD@@
</script>
</head>

<body>
<a class="skip-link" href="#free">Skip to the list</a>

<div class="page">

  <header class="masthead">
    <a class="masthead__brand" href="/">
      <span class="brand-dot" aria-hidden="true"></span>
      <span class="wordmark">@@NAME@@</span>
    </a>
    <nav aria-label="Primary">
      <a href="#free">The list</a>
      <a href="#who">Who we are</a>
      <a href="#suggest">Send us one</a>
    </nav>
  </header>

  <main>

    <div class="intro">
      <h1>Good stuff for home ed, all in one place.</h1>
      <p class="intro__lede">We're a home ed family in the UK, and we kept forgetting which websites were any good. So we started writing them down. Here's the list &mdash; free things first, paid things underneath, with a note on who each one suited us for.</p>
      <ul class="pills">
        <li class="pill">@@PILL_COUNT@@</li>
        <li class="pill">@@PILL_UPDATED@@</li>
        <li class="pill">@@PILL_STANDING@@</li>
      </ul>
    </div>

@@SECTIONS@@

  </main>

  <div class="two-up">
    <div class="two-up__col" id="who">
      <h2>Who's behind this</h2>
      <p>Just us &mdash; a family home educating in the UK. Nobody pays to be on the list, there are no affiliate links, and if something stops being useful we take it off rather than leave it sitting there. Each one says when we last checked it.</p>
    </div>
    <div class="two-up__col" id="suggest">
      <h2>Got one for us?</h2>
      <p>If something's worked for your lot, we'd love to hear about it. The link and a sentence about who it suited is plenty.</p>
      <a class="button" id="contact-email" href="#suggest" data-u="@@EMAIL_USER@@" data-d="@@EMAIL_DOMAIN@@"><span class="email-reverse">@@EMAIL_REVERSED@@</span></a>
    </div>
  </div>

  <footer class="footer">
    <p class="footer__disclaimer">These are other people's websites, so do have a look yourself before you rely on one &mdash; us listing it isn't a promise.</p>
    <div class="footer__meta">
      <span>Want an email when we add something? <a href="#suggest">Yes please</a></span>
      <span>&copy; @@YEAR@@ @@NAME@@</span>
    </div>
  </footer>

</div>

<script>
  /* Assemble the mailto at runtime so the address is not sitting in the
     markup for harvesters. Without JS the reversed text still reads
     correctly and the link falls back to the contact block. */
  (function () {
    var a = document.getElementById('contact-email');
    if (!a) return;
    var addr = a.dataset.u + String.fromCharCode(64) + a.dataset.d;
    a.href = 'mailto:' + addr;
    a.textContent = addr;
  })();
</script>

</body>
</html>
"""


# --- sidecar files ---------------------------------------------------------

def render_llms(data, updated, by_tier):
    site = data["site"]
    out = [
        f"# {site['name']}", "",
        f"> {site['description']}", "",
        "One UK home educating household keeps this list. Nobody pays to be on it,",
        "there are no affiliate links, and every entry carries the date it was last",
        "opened and checked. Entries that stop being useful are removed rather than",
        "left up.",
        "",
        f"Region: United Kingdom",
        f"Contact: via {site['url']}#suggest",
        f"Entries: {len(data['resources'])}",
        f"Last updated: {updated.isoformat()}",
        "",
    ]
    for tier in data["tiers"]:
        entries = by_tier[tier["id"]]
        out.append(f"## {tier['label']} — {tier['aside']}")
        out.append("")
        if entries:
            for r in entries:
                out.append(f"- [{r['title']}]({r['url']}): {r['description']} "
                           f"({' · '.join(r['tags'])}. Last checked {r['lastChecked']}.)")
        else:
            out.append(f"- {tier['empty']}")
        out.append("")
    out += [
        "## Optional", "",
        f"- [Full list]({site['url']}): every entry with its last-checked date.",
        "",
    ]
    return "\n".join(out)


def render_sitemap(site, updated):
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
  <url>
    <loc>{site['url']}</loc>
    <lastmod>{updated.isoformat()}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
    <image:image>
      <image:loc>{site['url']}og-image.png</image:loc>
      <image:title>{site['name']}</image:title>
    </image:image>
  </url>
</urlset>
"""


def main():
    data = load()
    page, updated, total = render_page(data)
    by_tier = {t["id"]: [r for r in data["resources"] if r["tier"] == t["id"]]
               for t in data["tiers"]}

    OUT.write_text(page, encoding="utf-8")
    (ROOT / "llms.txt").write_text(render_llms(data, updated, by_tier), encoding="utf-8")
    (ROOT / "sitemap.xml").write_text(render_sitemap(data["site"], updated), encoding="utf-8")

    print(f"built index.html  — {total} entries, last updated {updated.isoformat()}")
    for t in data["tiers"]:
        n = len(by_tier[t["id"]])
        print(f"    {t['label']:<5} {n} {'entry' if n == 1 else 'entries'}"
              + ("  (empty state shown)" if n == 0 else ""))
    print("built llms.txt, sitemap.xml")


if __name__ == "__main__":
    main()
