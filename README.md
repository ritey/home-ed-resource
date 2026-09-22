# Home Ed Resource

A single-page, hand-curated list of home education resources - free in one column,
paid in the other, with a monthly newsletter sign-up.

## Structure

Everything is in [`index.html`](index.html). No build step: Tailwind is loaded from
the Play CDN (`cdn.tailwindcss.com`) and fonts from Google Fonts.

Open the file directly in a browser, or serve it:

```sh
python3 -m http.server 8000
```

## Adding a resource

Copy an existing `<li>` card inside the free column (`#resources`) and change the
title, link, description and tags. Cards use a stretched-link pattern: the `<a>`
contains an absolutely-positioned `<span>` so the whole card is clickable, which
means **only one link per card**. All outbound links use
`target="_blank" rel="noopener noreferrer"`.

The paid column currently shows an empty state - replace that `<div>` with a `<ul>`
of cards in the same shape as the free column once there are entries.

## Newsletter drop-in

The form is a **placeholder** - it has no `action` and submitting it does nothing.
Replace the contents of `<div id="newsletter-embed">` with the embed snippet from
your provider (Buttondown, ConvertKit, MailerLite, Beehiiv, EmailOctopus, …), keeping
the wrapper `<div>` so the layout holds. Also remove the "sign-up isn't live yet"
note below the form once it is connected.

Provider embeds ship their own styling; expect to restyle their input and button to
match, or use the provider's plain-HTML/form-action option and keep the markup here.

## Before publishing

- [ ] Connect a real newsletter provider (see above)
- [ ] Update the `og:url` meta tag if the domain differs
- [ ] Update the "Last updated" `<time>` in the footer whenever the list changes
      (both the `datetime` attribute and the visible text)

## Deployment

Static - any host works (Netlify, Cloudflare Pages, GitHub Pages, S3). No server
side, no secrets, no env config.
