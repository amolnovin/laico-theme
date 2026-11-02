#!/usr/bin/env python3
"""Build the deployable ZIP for a GitHub Release.

The archive contains the whole static site plus docs, tools and design
references, at the archive root (unzip -> index.html, css/, js/, assets/ ...).

Excluded: .git, .qa (local QA tooling), archive/ (original laico.zip),
release/ (generated zips), .github/, caches and editor/OS files.

Usage:
    python3 tools/build-release-zip.py                              # default name
    python3 tools/build-release-zip.py laico-design-projects-v1.1.0.zip

The default name is the current combined release. Older release ZIPs stay in
release/ untouched so their download links keep working.
"""

import os
import sys
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DEFAULT_NAME = "laico-design-projects-v1.1.0.zip"
EXCLUDE_DIRS = {".git", ".qa", "archive", "release", ".github", "__pycache__", "node_modules", ".venv"}
EXCLUDE_FILES = {".gitignore", ".DS_Store", "Thumbs.db"}


def collect():
    items = []
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in EXCLUDE_DIRS and not d.startswith(".")]
        for name in sorted(filenames):
            if name in EXCLUDE_FILES or name.endswith((".pyc", ".log")):
                continue
            path = Path(dirpath) / name
            rel = path.relative_to(ROOT)
            if rel.parts[0] in EXCLUDE_DIRS:
                continue
            items.append((path, rel.as_posix()))
    return sorted(items, key=lambda x: x[1])


def main() -> int:
    name = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_NAME
    if not name.endswith(".zip"):
        name += ".zip"
    out = ROOT / "release" / name
    items = collect()
    out.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
        for path, rel in items:
            zf.write(path, rel)
    with zipfile.ZipFile(out) as zf:
        names = zf.namelist()
        assert zf.testzip() is None, "corrupt ZIP"
        for required in ("services.html", "projects.html", "css/services.css", "css/projects.css", "js/projects.js"):
            assert required in names, f"{required} missing"
    print(f"{out.relative_to(ROOT)}: {out.stat().st_size} bytes, {len(items)} files")
    return 0


if __name__ == "__main__":
    sys.exit(main())
