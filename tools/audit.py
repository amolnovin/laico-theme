#!/usr/bin/env python3
"""LAICO DESIGN — static template audit (no third-party dependencies).

Checks the 21 HTML pages, the stylesheet and the JavaScript dictionary for:

  * broken internal links, fragments and missing assets (ERROR)
  * duplicate id attributes (ERROR)
  * data-i18n / t() keys missing from the FA and EN dictionaries (ERROR)
  * orphan pages that no other page links to (WARN)
  * pages without an h1, images without alt/width/height (WARN)
  * placeholder contact/social values that must be replaced before launch (WARN)

Usage:
    python3 tools/audit.py             # print the report, fail only on errors
    python3 tools/audit.py --strict    # also fail on warnings

Run it from anywhere; paths are resolved relative to the repository root.
"""

from __future__ import annotations

import json
import re
import sys
from collections import Counter, defaultdict
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote

ROOT = Path(__file__).resolve().parent.parent
HTML_FILES = sorted(ROOT.glob("*.html"))
EXTERNAL = ("http:", "https:", "mailto:", "tel:", "data:", "javascript:", "//")

# Values that are clearly demo placeholders in this template.
PLACEHOLDERS = (
    r"\b000 0000\b",
    r'href="https://www\.instagram\.com/"',
    r'href="https://www\.youtube\.com/"',
    r'href="https://t\.me/"',
)


class Page(HTMLParser):
    """Collect the handful of attributes the audit needs from one HTML file."""

    def __init__(self, path: Path):
        super().__init__(convert_charrefs=True)
        self.path = path
        self.ids: list[str] = []
        self.refs: list[tuple[str, str]] = []  # (attribute value, context)
        self.images: list[dict[str, str | None]] = []
        self.i18n_keys: list[str] = []
        self.h1 = 0
        self.title = ""

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if a.get("id"):
            self.ids.append(a["id"])
        if tag == "h1":
            self.h1 += 1
        if tag == "a" and a.get("href"):
            self.refs.append((a["href"], "href"))
        if tag in {"img", "script", "link", "source"}:
            for key in ("src", "href", "srcset"):
                if a.get(key):
                    self.refs.append((a[key], key))
            if tag == "img":
                self.images.append(a)
        for key in ("data-i18n", "data-i18n-placeholder", "data-i18n-aria"):
            if a.get(key):
                self.i18n_keys.append(a[key])


def dictionary_keys(script: str, language: str) -> set[str]:
    """Extract the keys of the `fa:` / `en:` object literal in js/main.js."""
    match = re.search(r"\b" + language + r"\s*:\s*\{(.*?)\n    \}", script, re.S)
    if not match:
        return set()
    return set(re.findall(r'"([^"]+)"\s*:', match.group(1)))


def local_target(value: str) -> tuple[str, str] | None:
    """Return (relative path, fragment) for a local reference, else None."""
    if value.startswith("#"):
        return ("", unquote(value[1:]))
    if value.startswith(EXTERNAL):
        return None
    path, _, fragment = value.partition("#")
    return (unquote(path.split("?")[0]), unquote(fragment))


def main() -> int:
    errors: list[str] = []
    warnings: list[str] = []
    pages: dict[str, Page] = {}

    for path in HTML_FILES:
        page = Page(path)
        page.feed(path.read_text(encoding="utf-8"))
        pages[path.name] = page

    # --- references -------------------------------------------------------
    ids_by_page = {name: set(page.ids) for name, page in pages.items()}
    linked_pages: set[str] = set()
    missing_assets = 0
    for name, page in pages.items():
        for value, context in page.refs:
            if context == "srcset":  # not used by this template
                continue
            target = local_target(value)
            if target is None:
                continue
            rel, fragment = target
            if rel.endswith(".html"):
                linked_pages.add(Path(rel).name)
            if rel and not (ROOT / rel).is_file():
                errors.append(f"{name}: missing asset '{value}'")
                missing_assets += 1
            if fragment:
                scope = Path(rel).name if rel else name
                if scope in ids_by_page and fragment not in ids_by_page[scope]:
                    errors.append(f"{name}: anchor '{value}' has no matching id in {scope}")

    # --- CSS url() references ---------------------------------------------
    css = (ROOT / "css" / "styles.css").read_text(encoding="utf-8")
    for url in re.findall(r'url\(["\']?([^"\')]+)["\']?\)', css):
        if url.startswith(EXTERNAL):
            continue
        if not (ROOT / "css" / url).resolve().is_file():
            errors.append(f"css/styles.css: missing font/asset '{url}'")

    # --- duplicate ids ----------------------------------------------------
    for name, page in pages.items():
        for ident, count in Counter(page.ids).items():
            if count > 1:
                errors.append(f"{name}: duplicate id '{ident}' ({count}x)")

    # --- i18n dictionaries ------------------------------------------------
    script = (ROOT / "js" / "main.js").read_text(encoding="utf-8")
    fa, en = dictionary_keys(script, "fa"), dictionary_keys(script, "en")
    used = {key for page in pages.values() for key in page.i18n_keys}
    used |= set(re.findall(r'\bt\("([^"]+)"\)', script))
    for key in sorted(used - fa):
        errors.append(f"i18n: key '{key}' used in HTML/JS but missing from fa dict")
    for key in sorted(used - en):
        errors.append(f"i18n: key '{key}' used in HTML/JS but missing from en dict")
    unused = sorted(fa - used)

    # --- content hygiene --------------------------------------------------
    no_h1, no_alt, no_dims, empty_alt = [], 0, 0, 0
    for name, page in pages.items():
        if page.h1 != 1:
            no_h1.append(f"{name} ({page.h1} h1)")
        for img in page.images:
            if "alt" not in img:
                no_alt += 1
            elif not img["alt"]:
                empty_alt += 1
            if not img.get("width") or not img.get("height"):
                no_dims += 1
    if no_h1:
        warnings.append("pages without exactly one h1: " + ", ".join(no_h1))
    if no_alt or no_dims or empty_alt:
        warnings.append(
            f"images: {no_alt} without alt, {empty_alt} decorative alt=\"\", "
            f"{no_dims} without width/height"
        )

    orphans = sorted(set(pages) - linked_pages)
    if orphans:
        warnings.append("orphan pages (no inbound link): " + ", ".join(orphans))

    placeholder_hits: dict[str, list[str]] = defaultdict(list)
    for name, page in pages.items():
        text = page.path.read_text(encoding="utf-8")
        for pattern in PLACEHOLDERS:
            if re.search(pattern, text):
                placeholder_hits[pattern].append(name)
    for pattern, names in placeholder_hits.items():
        warnings.append(
            f"demo placeholder /{pattern}/ in {len(names)} pages: " + ", ".join(names)
        )

    # --- report -----------------------------------------------------------
    stats = {
        "html_pages": len(pages),
        "images_on_disk": len(list((ROOT / "assets" / "images").glob("*"))),
        "fonts_on_disk": len(list((ROOT / "assets" / "fonts").glob("*"))),
        "fa_dictionary_keys": len(fa),
        "en_dictionary_keys": len(en),
        "i18n_keys_used": len(used),
        "i18n_keys_never_used": len(unused),
        "broken_references": missing_assets,
        "errors": len(errors),
        "warnings": len(warnings),
    }
    print("LAICO DESIGN — template audit")
    print(json.dumps(stats, indent=2, ensure_ascii=False))
    print("\nERRORS" if errors else "\nNo errors.")
    for line in errors:
        print("  ✗", line)
    print("\nWARNINGS" if warnings else "\nNo warnings.")
    for line in warnings:
        print("  !", line)
    if unused:
        print("\nUnused dictionary keys:", ", ".join(unused))

    strict = "--strict" in sys.argv
    return 1 if errors or (strict and warnings) else 0


if __name__ == "__main__":
    sys.exit(main())
