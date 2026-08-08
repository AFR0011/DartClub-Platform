from __future__ import annotations

import os
import subprocess
from collections import defaultdict
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]

CATEGORIES = {
    "vendor": "vendor/",
    "membership_uploads": "files/applications/membership/",
    "blog_images": "files/media/images/blog/",
    "gallery_images": "files/media/images/gallery/",
    "other_images": "files/media/Other Images/",
}


def tracked_files() -> list[str]:
    result = subprocess.run(
        ["git", "ls-files", "-z"],
        cwd=ROOT,
        check=True,
        capture_output=True,
    )
    return [entry.decode("utf-8") for entry in result.stdout.split(b"\0") if entry]


def size_of(relative: str) -> int:
    path = ROOT / relative
    try:
        return path.stat().st_size if path.is_file() else 0
    except OSError:
        return 0


def human_bytes(value: int) -> str:
    units = ["B", "KiB", "MiB", "GiB"]
    amount = float(value)
    for unit in units:
        if amount < 1024 or unit == units[-1]:
            return f"{amount:.2f} {unit}"
        amount /= 1024
    return f"{value} B"


def main() -> None:
    files = tracked_files()
    totals: dict[str, list[int]] = defaultdict(lambda: [0, 0])
    total_bytes = 0

    for relative in files:
        file_size = size_of(relative)
        total_bytes += file_size
        for name, prefix in CATEGORIES.items():
            if relative.startswith(prefix):
                totals[name][0] += 1
                totals[name][1] += file_size

    print(f"tracked files: {len(files)}")
    print(f"tracked working-tree bytes: {human_bytes(total_bytes)}")
    for name in CATEGORIES:
        count, byte_count = totals[name]
        print(f"{name}: {count} files / {human_bytes(byte_count)}")

    largest = sorted(((size_of(path), path) for path in files), reverse=True)[:20]
    print("largest tracked files:")
    for file_size, relative in largest:
        print(f"  {human_bytes(file_size):>12}  {relative}")


if __name__ == "__main__":
    main()
