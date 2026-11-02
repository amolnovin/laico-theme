#!/usr/bin/env python3
"""Extract the projects-page card photograph from the design references.

projects.png / reference/projects/image-3.png show six cards that all use the
same night-time commercial-complex photo, with the card text burned into the
screenshot. The clean (text-free) version of that exact photograph appears in
reference/خدمات.png (the services design reference), so the card JPG is cut
from there. All other card images are the template's existing project photos.

Requires Pillow:  python3 -m pip install pillow
Run from anywhere: python3 tools/extract-project-images.py
"""

from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "reference" / "خدمات.png"
OUT = ROOT / "assets" / "images" / "projects-card.jpg"

# Clean crop of the night-time complex photo (same image as the cards in
# projects.png / image-3.png), measured on the 1291x4571 services screenshot.
CROP = (669, 1688, 1269, 2138)


def main() -> None:
    if not SRC.is_file():
        raise SystemExit(f"Missing reference screenshot: {SRC}")
    im = Image.open(SRC).convert("RGB")
    OUT.parent.mkdir(parents=True, exist_ok=True)
    im.crop(CROP).save(OUT, "JPEG", quality=90, optimize=True, progressive=True)
    print(f"{OUT.relative_to(ROOT)}  {Image.open(OUT).size}  {OUT.stat().st_size} bytes")


if __name__ == "__main__":
    main()
