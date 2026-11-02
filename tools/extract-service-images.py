#!/usr/bin/env python3
"""Rebuild the services-page images from the desktop design reference.

Source: reference/خدمات.png (1291 x 4571 screenshot of services.html, desktop).
The 8 detail crops (reference/image-1.png ... image-8.png) were used to read the
text and to confirm the placement of each section; the photo regions below are
cut from the full-resolution screenshot so the exported JPGs stay sharp.

Requires Pillow:  python3 -m pip install pillow
Run from anywhere: python3 tools/extract-service-images.py
"""

from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "reference" / "خدمات.png"
DST = ROOT / "assets" / "images"

# name -> crop box (left, top, right, bottom) in the 1291x4571 reference image
CROPS = {
    "services-hero.jpg":        (0, 100, 1291, 460),     # image-1 / خدمات.png hero (no baked-in text)
    "services-clinic.jpg":      (672, 693, 1272, 1143),  # image-2 — ۰۱ مطب و فضای درمانی
    "services-sport.jpg":       (41, 1189, 641, 1639),   # image-3 — ۰۲ محوطه ورزشی و باشگاهی
    "services-complex.jpg":     (669, 1688, 1269, 2138), # image-4 — ۰۳ مجتمع های تفریحی
    "services-cafe.jpg":        (45, 2179, 645, 2629),   # image-5 — ۰۴ کافه و رستوران
    "services-residential.jpg": (669, 2670, 1269, 3120), # image-6 — ۰۵ آشپزخانه و فضای مسکونی
    "services-why.jpg":         (42, 3343, 411, 3665),   # image-7 — نوار چرا لایکودیزاین؟
}

# image-8 is the consultation/form section: it contains no photo, it was used
# as the layout + copy reference for the .svc-consult block in services.html.


def main() -> None:
    if not SRC.is_file():
        raise SystemExit(f"Missing reference screenshot: {SRC}")
    im = Image.open(SRC).convert("RGB")
    if im.size != (1291, 4571):
        print(f"Warning: unexpected reference size {im.size}; crop coordinates may need updating")
    DST.mkdir(parents=True, exist_ok=True)
    for name, box in CROPS.items():
        out = DST / name
        im.crop(box).save(out, "JPEG", quality=90, optimize=True, progressive=True)
        print(f"{out.relative_to(ROOT)}  {Image.open(out).size}  {out.stat().st_size} bytes")


if __name__ == "__main__":
    main()
