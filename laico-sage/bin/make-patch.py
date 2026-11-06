#!/usr/bin/env python3
"""Build a signed/checksummed Laico Sage patch ZIP package (`patch.json` + `files/`).

Examples:
  python3 laico-sage/bin/make-patch.py \
      --id patch-1.4.1 \
      --from-version 1.4.0 \
      --to-version 1.4.1 \
      --description "بهبود بخش پروژه‌ها و رفع اشکال جزئی" \
      --files resources/views/front-page.blade.php app/Support/Hero.php \
      --output /tmp/laico-patch-1.4.1.zip

  # Or automatically detect changed files between two git commits inside laico-sage/:
  python3 laico-sage/bin/make-patch.py \
      --git-range HEAD~1..HEAD \
      --to-version 1.4.1 \
      --hmac-secret "my-secret" \
      --output /tmp/laico-patch-1.4.1.zip
"""

from __future__ import annotations

import argparse
import hashlib
import hmac
import json
import subprocess
import zipfile
from datetime import datetime, timezone
from pathlib import Path

THEME_ROOT = Path(__file__).resolve().parent.parent


def canonical_payload(manifest: dict) -> bytes:
    clean = {k: v for k, v in manifest.items() if k not in ("signature", "hmac_sha256")}
    return json.dumps(clean, sort_keys=True, ensure_ascii=False, separators=(",", ":")).encode("utf-8")


def sha256_bytes(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


ALLOWED_EXTENSIONS = {
    "php", "css", "js", "mjs", "json", "svg", "png", "jpg", "jpeg",
    "webp", "woff2", "mp4", "po", "mo", "pot", "md", "txt",
}


def is_patchable_path(rel_path: str) -> bool:
    ext = Path(rel_path).suffix.lower().lstrip(".")
    return ext in ALLOWED_EXTENSIONS and not rel_path.startswith("bin/")


def collect_git_changes(git_range: str) -> tuple[list[str], list[str]]:
    repo_root = subprocess.check_output(["git", "rev-parse", "--show-toplevel"], cwd=THEME_ROOT, text=True).strip()
    rel_theme = THEME_ROOT.relative_to(Path(repo_root))
    out = subprocess.check_output(
        ["git", "diff", "--no-renames", "--name-status", git_range, "--", str(rel_theme)],
        cwd=repo_root,
        text=True,
    )
    changed, deleted = [], []
    prefix = f"{rel_theme.as_posix()}/" if str(rel_theme) != "." else ""
    for line in out.splitlines():
        if not line.strip():
            continue
        parts = line.split("\t")
        status, path = parts[0], parts[-1]
        if prefix and path.startswith(prefix):
            path = path[len(prefix) :]
        if not is_patchable_path(path):
            continue
        if status.startswith("D"):
            deleted.append(path)
        else:
            changed.append(path)
    return changed, deleted


def main() -> int:
    parser = argparse.ArgumentParser(description="Build a Laico Sage theme patch ZIP.")
    parser.add_argument("--id", default="", help="Patch identifier (e.g. patch-1.4.1)")
    parser.add_argument("--from-version", default="", help="Required exact current version (optional)")
    parser.add_argument("--min-version", default="1.4.0", help="Minimum compatible theme version")
    parser.add_argument("--to-version", required=True, help="New theme version after patch")
    parser.add_argument("--description", default="بسته بروزرسانی قالب لایکو دیزاین", help="Patch summary")
    parser.add_argument("--files", nargs="*", default=[], help="Relative paths inside laico-sage/ to include")
    parser.add_argument("--delete-files", nargs="*", default=[], help="Relative paths inside laico-sage/ to delete")
    parser.add_argument("--git-range", default="", help="Git revision range, e.g. v1.4.0..HEAD")
    parser.add_argument("--hmac-secret", default="", help="Optional HMAC-SHA256 signing secret")
    parser.add_argument("--output", required=True, help="Output .zip path")
    args = parser.parse_args()

    changed = list(args.files)
    deleted = list(args.delete_files)

    if args.git_range:
        git_changed, git_deleted = collect_git_changes(args.git_range)
        changed.extend(git_changed)
        deleted.extend(git_deleted)

    changed = sorted(set(changed))
    deleted = sorted(set(deleted))

    if not changed and not deleted:
        raise SystemExit("No changed or deleted files specified.")

    files_manifest: dict[str, dict[str, str]] = {}
    file_blobs: dict[str, bytes] = {}
    for rel in changed:
        norm = rel.replace("\\", "/").lstrip("/")
        full = THEME_ROOT / norm
        if not full.is_file():
            raise SystemExit(f"Missing file in theme root: {norm}")
        blob = full.read_bytes()
        file_blobs[norm] = blob
        files_manifest[norm] = {"sha256": sha256_bytes(blob)}

    patch_id = args.id or f"patch-{args.to_version}"
    manifest: dict = {
        "id": patch_id,
        "target_theme": "laico-sage",
        "min_version": args.min_version,
        "to_version": args.to_version,
        "description": args.description,
        "created_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "files": files_manifest,
        "deleted_files": deleted,
    }
    if args.from_version:
        manifest["from_version"] = args.from_version

    if args.hmac_secret:
        payload = canonical_payload(manifest)
        manifest["hmac_sha256"] = hmac.new(args.hmac_secret.encode("utf-8"), payload, hashlib.sha256).hexdigest()

    out_path = Path(args.output).resolve()
    out_path.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(out_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        zf.writestr("patch.json", json.dumps(manifest, indent=2, ensure_ascii=False) + "\n")
        for rel, blob in file_blobs.items():
            zf.writestr(f"files/{rel}", blob)

    print(f"Built patch package: {out_path} ({len(file_blobs)} updated, {len(deleted)} deleted)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
