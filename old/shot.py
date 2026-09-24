#!/usr/bin/env python3
"""
Capture entry thumbnails for resources.json.

Screenshots each resource's site with headless Chrome, dismisses the usual
cookie/consent dialogs, and writes a 532x280 (2x of the 266x140 display size)
PNG into images/.

    python3 shot.py                 # any resource missing an image
    python3 shot.py idea.org.uk     # just this domain
    python3 shot.py --all           # re-capture everything

Requires: Google Chrome, websocket-client (pip install websocket-client),
ImageMagick. If a capture fails the entry simply keeps no image and the page
falls back to a tinted tile, so this is never load-bearing.
"""

import json
import os
import re
import shutil
import socket
import subprocess
import sys
import time
import urllib.request
from pathlib import Path

import websocket  # type: ignore

ROOT = Path(__file__).parent
IMAGES = ROOT / "images"
CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

SHOT_W, SHOT_H = 1200, 630      # capture viewport
OUT_W, OUT_H = 532, 280         # 2x of the 266x140 slot

# Buttons that dismiss a consent dialog. Ordered: most specific first.
CONSENT_JS = r"""
(() => {
  const pats = [
    /^(accept all|allow all|accept cookies|accept & close|i accept)$/i,
    /^(consent|accept|agree|i agree|allow|got it|ok|okay)$/i,
    /(accept|agree|consent)/i,
  ];
  const clickable = Array.from(document.querySelectorAll(
    'button, a[role=button], [role=button], input[type=button], input[type=submit]'
  ));
  for (const pat of pats) {
    for (const el of clickable) {
      const t = (el.innerText || el.value || '').trim();
      if (!t || t.length > 40) continue;
      if (!pat.test(t)) continue;
      const r = el.getBoundingClientRect();
      if (r.width < 2 || r.height < 2) continue;
      el.click();
      return 'clicked: ' + t;
    }
  }
  // Nothing to click — hide obvious fixed overlays instead.
  let hidden = 0;
  document.querySelectorAll('div,section,aside').forEach(el => {
    const s = getComputedStyle(el);
    if ((s.position === 'fixed' || s.position === 'sticky') && el.offsetHeight > 60) {
      const id = ((el.id || '') + ' ' + (el.className || '')).toString().toLowerCase();
      if (/cookie|consent|gdpr|privacy|banner|modal|overlay|backdrop/.test(id)) {
        el.style.display = 'none'; hidden++;
      }
    }
  });
  return hidden ? 'hid ' + hidden + ' overlay(s)' : 'no consent UI found';
})()
"""


def free_port():
    s = socket.socket()
    s.bind(("127.0.0.1", 0))
    p = s.getsockname()[1]
    s.close()
    return p


class Tab:
    """Minimal Chrome DevTools Protocol client."""

    def __init__(self, ws_url):
        self.ws = websocket.create_connection(ws_url, timeout=45)
        self.n = 0

    def send(self, method, **params):
        self.n += 1
        self.ws.send(json.dumps({"id": self.n, "method": method, "params": params}))
        while True:
            msg = json.loads(self.ws.recv())
            if msg.get("id") == self.n:
                if "error" in msg:
                    raise RuntimeError(f"{method}: {msg['error']}")
                return msg.get("result", {})

    def evaluate(self, expr):
        r = self.send("Runtime.evaluate", expression=expr, returnByValue=True,
                      awaitPromise=True)
        return r.get("result", {}).get("value")

    def close(self):
        try:
            self.ws.close()
        except Exception:
            pass


def launch():
    port = free_port()
    prof = f"/tmp/shotpy-{port}"
    proc = subprocess.Popen(
        [CHROME, "--headless=new", "--disable-gpu", "--no-sandbox", "--hide-scrollbars",
         "--mute-audio", "--disable-extensions", "--remote-allow-origins=*",
         f"--remote-debugging-port={port}",
         f"--user-data-dir={prof}", f"--window-size={SHOT_W},{SHOT_H}", "about:blank"],
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    for _ in range(80):
        try:
            v = urllib.request.urlopen(f"http://127.0.0.1:{port}/json/version", timeout=1).read()
            json.loads(v)
            return proc, port, prof
        except Exception:
            time.sleep(0.25)
    proc.kill()
    raise RuntimeError("Chrome did not expose a debugging port")


def capture(port, url, dest):
    # Newer Chrome requires PUT on /json/new.
    req = urllib.request.Request(f"http://127.0.0.1:{port}/json/new?about:blank", method="PUT")
    tabs = json.loads(urllib.request.urlopen(req, timeout=10).read())
    tab = Tab(tabs["webSocketDebuggerUrl"])
    tab_id = tabs["id"]
    try:
        tab.send("Page.enable")
        tab.send("Runtime.enable")
        tab.send("Emulation.setDeviceMetricsOverride", width=SHOT_W, height=SHOT_H,
                 deviceScaleFactor=1, mobile=False)
        tab.send("Page.navigate", url=url)
        time.sleep(6)
        note = tab.evaluate(CONSENT_JS)
        time.sleep(2.5)
        # Scroll back to the top in case dismissing moved the page.
        tab.evaluate("window.scrollTo(0,0)")
        time.sleep(0.6)
        data = tab.send("Page.captureScreenshot", format="png")["data"]
        import base64
        raw = ROOT / ".shot-raw.png"
        raw.write_bytes(base64.b64decode(data))
        # Fill the 1.9:1 slot from the top of the page.
        subprocess.run(["magick", str(raw), "-resize", f"{OUT_W}x{OUT_H}^",
                        "-gravity", "north", "-extent", f"{OUT_W}x{OUT_H}",
                        "-strip", "-quality", "82", str(dest)], check=True)
        raw.unlink(missing_ok=True)
        return note
    finally:
        tab.close()
        try:
            urllib.request.urlopen(f"http://127.0.0.1:{port}/json/close/{tab_id}", timeout=5).read()
        except Exception:
            pass


def main():
    args = [a for a in sys.argv[1:]]
    do_all = "--all" in args
    only = [a for a in args if not a.startswith("--")]

    data = json.loads((ROOT / "resources.json").read_text(encoding="utf-8"))
    IMAGES.mkdir(exist_ok=True)

    targets = []
    for r in data["resources"]:
        slug = re.sub(r"[^a-z0-9]+", "-", r["domain"].lower()).strip("-")
        dest = IMAGES / f"{slug}.png"
        if only and r["domain"] not in only and slug not in only:
            continue
        if not do_all and not only and dest.exists():
            continue
        targets.append((r, slug, dest))

    if not targets:
        print("nothing to capture (use --all to re-shoot)")
        return

    if not shutil.which("magick"):
        sys.exit("error: ImageMagick 'magick' not on PATH")

    proc, port, prof = launch()
    changed = False
    try:
        for r, slug, dest in targets:
            print(f"  {r['domain']:<24} ", end="", flush=True)
            try:
                note = capture(port, r["url"], dest)
                print(f"-> images/{dest.name}  ({note})")
                r["image"] = f"images/{dest.name}"
                changed = True
            except Exception as e:
                print(f"FAILED: {type(e).__name__}: {e}")
                print("      (entry will fall back to a tinted tile)")
    finally:
        proc.terminate()
        try:
            proc.wait(timeout=10)
        except Exception:
            proc.kill()
        shutil.rmtree(prof, ignore_errors=True)

    if changed:
        (ROOT / "resources.json").write_text(
            json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        print("\nupdated resources.json — now run: python3 build.py")
        print("Check each thumbnail before committing; a consent dialog or a cookie")
        print("banner that survived will be visible in the image.")


if __name__ == "__main__":
    main()
