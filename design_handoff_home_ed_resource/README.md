# Handoff: Home Ed Resource redesign

## Overview

A redesign of homeedresource.co.uk — a small, hand-curated directory of UK home education resources. One page.

The brief: remove the "AI-generated" feel of the current site (teal/gradient palette, generic card grid, over-polished marketing copy) and replace it with something warm, plain-spoken and clearly maintained by real people. An earlier, more editorial version was rejected as too formal; this direction is deliberately friendly and conversational.

Content is currently tiny (2 free entries, 0 paid), so the page must not look broken when near-empty. The empty "Paid" section is styled as a written explanation, not a placeholder card.

## About the design files

`home-ed-resource.html` is a **design reference created in HTML** — a prototype showing intended look, structure and copy. It is not production code to copy directly. Recreate it in the target codebase using that project's framework and conventions. If no codebase exists yet: this is a static content site with zero interactivity, so a static-site generator (Astro, Eleventy, Hugo) or plain HTML + CSS is the right choice. Do not use a SPA framework.

The reference uses inline styles because of the tool it was authored in. **Do not ship inline styles** — convert to a stylesheet or the codebase's styling solution.

## Fidelity

**High-fidelity.** Colours, type, spacing and copy are final — recreate faithfully. Two things are deliberately not in the reference and must be implemented: responsive behaviour, and the resource thumbnails (grey boxes in the reference; see "Images" below).

## Layout, section by section

Page column: `max-width: 880px`, centred, on a `#e8e6e1` desk. Page background `#fefbf6`. Page gutter `52px`.

### 1. Header
- Padding `22px 52px`, background `#fdf3e4`, bottom border `2px solid #26211c`.
- Flex row, space-between, `align-items: center`.
- Left: a 13px terracotta dot (`oklch(0.62 0.13 45)`, 50% radius) + wordmark "Home Ed Resource" — Bricolage Grotesque 600, 23px, `letter-spacing: -0.015em`.
- Right: nav, Karla 15px, colour `#4a423a`, gap `24px`, no underline. Items: "The list", "Who we are", "Send us one".
- Hover (add in build): colour → `#26211c`, 120ms ease.

### 2. Intro
- Padding `52px 52px 44px`, bottom border `1px solid #ece2d3`, flex column gap `20px`.
- H1: Bricolage Grotesque 600, 50px, `line-height: 1.1`, `letter-spacing: -0.025em`, `max-width: 18ch`.
  "Good stuff for home ed, all in one place."
- Lede: Karla 19.5px, `line-height: 1.6`, colour `#4a423a`, `max-width: 52ch`.
  "We're a home ed family in the UK, and we kept forgetting which websites were any good. So we started writing them down. Here's the list — free things first, paid things underneath, with a note on who each one suited us for."
- Three pills: 14px, padding `7px 14px`, `border-radius: 100px`, background `#f5ebda`, colour `#4a423a`, gap `10px`.
  "2 things so far" · "Updated 22 September" · "No ads, no affiliate links".
  **The first two are derived from data at build time, not hardcoded.**

### 3. Section headings ("Free", "Paid")
- Padding `40px 52px 8px` (Free) / `30px 52px 8px` (Paid), flex row, `align-items: baseline`, gap `14px`.
- H2: Bricolage Grotesque 600, 29px, `letter-spacing: -0.02em`.
- Aside: Karla 15.5px, colour `#5c5349`, prefixed with an em dash.
  Free: "— genuinely free, not a trial that turns into a bill"
  Paid: "— nothing here yet, and that's on purpose"

### 4. Resource entry (the repeating component)
- `<article>`, `display: grid`, `grid-template-columns: 1fr 268px`, gap `32px`, `align-items: start`, padding `26px 0`, bottom border `1px solid #ece2d3`. Entries sit inside a `0 52px` container.
- **Left column** — flex column, gap `11px`:
  - Top row: space-between, baseline.
    - Title link: Bricolage Grotesque 500, 27px, `letter-spacing: -0.02em`, colour `#26211c`, no underline. Hover: underline, 1px, ~4px offset.
    - Checked line: Karla 13.5px, colour `#6b6157`, `white-space: nowrap` — "we last looked 18 Sep".
  - Description: Karla 18px, `line-height: 1.6`, colour `#3a332c`, `max-width: 44ch`.
  - Tag row: flex wrap, gap `8px`. Tags 13.5px, padding `5px 12px`, `border-radius: 100px`, background `#f0f3ec`, colour `#43503f`. Final item is the domain — no pill, padding `5px 0 5px 4px`, colour `oklch(0.52 0.12 45)`.
- **Right column** — the thumbnail (see below): 266×140, `border-radius: 13px`, wrapped in a link to the resource with a `1px solid #ece2d3` border and `border-radius: 14px; overflow: hidden`.

Entry data in the design:

| Title | URL | Checked | Tags |
|---|---|---|---|
| iDEA | idea.org.uk | 18 Sep | Good from about 8 · Do it in any order · Fine on a phone |
| Do Revision | dorevision.com | 11 Sep | Teens · GCSE · Free bit is usable |

Descriptions (verbatim):
- iDEA: "Little online modules about computers, money and work skills. You earn badges as you go, and they add up to a Bronze, Silver or Gold award. Handy if you like having something to show for the term."
- Do Revision: "Revision questions and a plan for what to do next. The plan is the bit we found useful — without a school timetable, someone has to decide what gets revised today, and this does it for you."

External links: `target="_blank" rel="noopener noreferrer nofollow"`.

### 5. Empty paid state
Padding `6px 52px 38px`, Karla 18px, `line-height: 1.6`, colour `#4a423a`, `max-width: 54ch`:
"We're trying a couple of paid things at the moment. We'd rather use them for a term before telling you to spend money on them, so this bit stays empty for now."

### 6. Two-up block
- Background `#fdf3e4`, top border `1px solid #ece2d3`, padding `40px 52px`, `grid-template-columns: 1fr 1fr`, gap `44px`.
- H3: Bricolage Grotesque 600, 23px, `letter-spacing: -0.02em`. Body: Karla 17px, `line-height: 1.62`, colour `#3a332c`.
- Left — "Who's behind this": "Just us — a family home educating in the UK. Nobody pays to be on the list, there are no affiliate links, and if something stops being useful we take it off rather than leave it sitting there. Each one says when we last checked it."
- Right — "Got one for us?": "If something's worked for your lot, we'd love to hear about it. The link and a sentence about who it suited is plenty."
  Then a button-style mailto: 16px, weight 500, colour `#26211c`, background `oklch(0.78 0.1 75)`, padding `11px 20px`, `border-radius: 100px`, `align-self: flex-start`. Hover: darken background ~6%.
  **Obfuscate the address** — the current site uses Cloudflare email protection; keep an equivalent.

### 7. Footer
- Padding `24px 52px 30px`, flex row space-between, `align-items: flex-start`, gap `32px`, Karla 13.5px, `line-height: 1.7`, colour `#6b6157`.
- Left, `max-width: 56ch`: "These are other people's websites, so do have a look yourself before you rely on one — us listing it isn't a promise." (This replaces the long legal disclaimer; if legal wants the full text, give it a `/disclaimer` page linked from here.)
- Right, right-aligned, gap `4px`: "Want an email when we add something? **Yes please**" (link, colour `#26211c`) and "© 2026 Home Ed Resource".
- The newsletter was deliberately reduced from a whole section to this one line. When the mailing provider is connected, "Yes please" opens the provider's hosted form or reveals a small inline field — do not reinstate a hero-sized newsletter block.

## Images

Each entry carries a 266×140 thumbnail (≈1.9:1, i.e. og:image ratio), `border-radius: 13px`, `object-fit: cover`, inside a bordered rounded link. In the reference these are grey placeholder boxes.

Implementation guidance:
- Prefer a **stored screenshot** per resource, committed to the repo and referenced from the resource data. Predictable, fast, no third-party dependency.
- If pulling `og:image` instead: fetch and cache at **build time**, never hotlink — many sites block cross-origin embedding, serve wildly off-ratio images, or have no `og:image` at all.
- **Fallback is required**: when no image exists, render a tinted tile (`#f3ece1`) with the domain name in the entry's tag colour. A broken or empty frame is worse than no image.
- `loading="lazy"`, `decoding="async"`, explicit `width`/`height` to avoid layout shift.
- Thumbnails are decorative — the title next to them is the real link — so `alt=""` on the image, and the wrapping link needs no separate accessible name beyond the entry title (or give it `aria-hidden="true" tabindex="-1"` to keep tab order clean).

## Interactions & behaviour

Almost none, by design:
- Nav anchors scroll to the list / who-we-are / contact.
- Link hovers as noted above, ~120ms ease.
- Visible focus rings on all links and the mailto button — 2px outline `#26211c`, 2px offset. Do not remove default focus styling.
- Skip-to-content link, visually hidden until focused (the current site has one — keep it).
- No JS required. No scroll animations, no reveal-on-scroll, no cookie banner unless analytics are added.

## State management

None. Static. The resource list must live as data (JSON/YAML/Markdown frontmatter), not hand-written markup:

```
title, url, domain, tier ("free" | "paid"), description,
tags: string[], lastChecked: ISO date, image?: path
```

Derived at build: per-tier counts, the "2 things so far" pill, and "Updated <date>" = most recent `lastChecked`.

## Design tokens

Colours
- Ink: `#26211c`
- Body text: `#3a332c`
- Secondary text: `#4a423a`
- Muted text: `#5c5349` / `#6b6157` (both AA on the paper tones — do not lighten)
- Paper: `#fefbf6`
- Paper, warm band (header + two-up block): `#fdf3e4`
- Rule: `#ece2d3`
- Pill, meta: `#f5ebda` on `#4a423a`
- Pill, tag: `#f0f3ec` on `#43503f`
- Image placeholder tile: `#f3ece1`
- Accent dot: `oklch(0.62 0.13 45)`
- Accent domain text: `oklch(0.52 0.12 45)`
- Button: `oklch(0.78 0.1 75)` with ink text

Typography
- Headings: Bricolage Grotesque 500/600 — 50 / 29 / 27 / 23px, `letter-spacing: -0.015` to `-0.025em`
- Body/UI: Karla 400/500 — 19.5 / 18 / 17 / 15.5 / 15 / 14 / 13.5px
- Line heights: 1.1 (H1), 1.6–1.62 (body), 1.7 (footer)
- Measure caps: 18ch (H1), 44–56ch (body)
- Self-host both fonts (woff2, `font-display: swap`) rather than hotlinking Google Fonts.

Radius: `100px` on pills and the button, `13–14px` on thumbnails, `50%` on the header dot. Nothing else is rounded. No shadows anywhere, no gradients — that flatness is intentional.

Spacing: 52px page gutter; section paddings 22–52px; stack gaps 8–20px; entry grid gap 32px.

## Responsive behaviour (not in the reference — implement it)

- Gutters 52px → 24px below ~700px.
- Below ~700px the entry grid collapses to one column with the thumbnail **first**, full-width, same 1.9:1 ratio.
- Below ~640px the entry top row wraps: the "we last looked" line moves under the title, left-aligned.
- Below ~820px the two-up block becomes one column, gap 32px.
- H1 scales to ~34–38px on small screens, `line-height` ~1.15.
- Header nav wraps under the wordmark below ~560px; keep tap targets ≥44px.

## Accessibility

- Muted greys are at the AA limit — do not lighten any text colour.
- One `<h1>`; section labels are real `<h2>`s; entry titles `<h3>`.
- Entry title is the link — no "read more".
- Tag pills are plain text, not buttons, unless filtering is built later.

## Files

- `home-ed-resource.html` — the design reference (open in a browser).
- Source of truth in the design project: `Home Ed Resource redesign.dc.html`, option `2a`. Options `1a` and `1b` are earlier, more formal directions and are **not** part of this handoff.
