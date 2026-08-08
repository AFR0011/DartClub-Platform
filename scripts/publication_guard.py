from __future__ import annotations

import re
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
FAILURES: list[str] = []


def fail(message: str) -> None:
    FAILURES.append(message)


def read(relative: str) -> str:
    path = ROOT / relative
    if not path.is_file():
        fail(f"missing required publication file: {relative}")
        return ""
    return path.read_text(encoding="utf-8", errors="replace")


def tracked_files() -> list[str]:
    result = subprocess.run(
        ["git", "ls-files"],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
    )
    return [line.strip() for line in result.stdout.splitlines() if line.strip()]


def main() -> int:
    config = read("services/config.php")
    bootstrap = read("services/app_bootstrap.php")
    login = read("services/login.php")
    create_player = read("services/create_player.php")
    membership = read("services/submit_membership_application.php")
    membership_download = read("services/download_membership_application.php")
    membership_htaccess = read("files/applications/membership/.htaccess")
    sanitizer = read("services/shared/html_sanitizer.php")
    create_blog = read("services/create_blog.php")
    get_blogs = read("services/get_blogs.php")
    sql = read("dart_club.sql")
    readme = read("README.md")
    security = read("SECURITY.md")
    publication = read("PUBLICATION.md")
    composer = read("composer.json")
    read("services/config.local.example.php")

    if "APP_DB_PASS', '1234'" in config or "APP_DB_PASS\", \"1234" in config:
        fail("services/config.php still contains the historical hardcoded DB password")

    for marker in (
        "session.use_strict_mode",
        "session.use_only_cookies",
        "'httponly' => true",
        "'samesite' => 'Lax'",
    ):
        if marker not in bootstrap:
            fail(f"session hardening marker missing: {marker}")

    for marker in (
        "app_enforce_same_origin_mutation",
        "HTTP_ORIGIN",
        "Cross-origin state-changing requests are not allowed.",
    ):
        if marker not in bootstrap:
            fail(f"same-origin mutation boundary missing: {marker}")

    if "session_regenerate_id(true)" not in login:
        fail("login does not rotate the session identifier")

    if "Temporary password:" in create_player or "temporary password: {$rawPassword}" in create_player:
        fail("create_player.php still sends a temporary password by email")

    if "finfo_file" not in membership:
        fail("membership document upload does not validate server-detected MIME type")
    if "@unlink($destination)" not in membership:
        fail("membership upload does not clean up moved files after DB failure")

    if "is_manager_or_admin()" not in membership_download:
        fail("membership download endpoint is not manager/admin-gated")
    if "Require all denied" not in membership_htaccess:
        fail("membership upload directory is not blocked from direct Apache access")

    for marker in (
        "DOMDocument",
        "app_safe_rich_html_url",
        "noopener noreferrer",
        "allowedAttributes",
    ):
        if marker not in sanitizer:
            fail(f"rich HTML sanitizer is missing allowlist/security marker: {marker}")
    for name, text in (("create_blog.php", create_blog), ("get_blogs.php", get_blogs)):
        if "shared/html_sanitizer.php" not in text or "app_sanitize_rich_html" not in text:
            fail(f"{name} does not use the shared rich HTML sanitizer")

    plaintext_seed_passwords = ("adminpass", "managerpass", "memberpass", "playerpass")
    for password in plaintext_seed_passwords:
        if re.search(rf"['\"]{re.escape(password)}['\"]", sql):
            fail(f"dart_club.sql still stores local seed password in plaintext: {password}")

    for path in (ROOT / "services").rglob("*.php"):
        if path.name == "app_bootstrap.php":
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        if "$exception->getMessage()" in text:
            fail(f"raw exception message returned/used in service: {path.relative_to(ROOT)}")

    for marker in (
        "No public source-code license has been selected yet",
        "clean modern history",
        "PHPMailer 6.12",
    ):
        if marker not in readme:
            fail(f"README missing publication boundary/state marker: {marker}")
    if "Historical repository boundary" not in security:
        fail("SECURITY.md must document the historical Git boundary")
    if "License blocker" not in publication:
        fail("PUBLICATION.md must retain the explicit license blocker")
    if '"license": "proprietary"' not in composer:
        fail("composer.json must remain explicitly proprietary until a public source license is chosen")

    tracked = tracked_files()
    if any(path.startswith("vendor/") for path in tracked):
        fail("Composer vendor files are still tracked")
    if "progress.md" in tracked:
        fail("generated Cursor progress transcript is still tracked")

    public_text_paths = [ROOT / "README.md", ROOT / "PUBLICATION.md", ROOT / "SECURITY.md"]
    public_text_paths.extend((ROOT / "docs").rglob("*.md"))
    private_path_marker = "C:\\Users\\Ali\\"
    for path in public_text_paths:
        text = path.read_text(encoding="utf-8", errors="replace")
        if private_path_marker in text:
            fail(f"machine-specific home-directory path remains in public documentation: {path.relative_to(ROOT)}")

    if FAILURES:
        print("publication guard failed:")
        for item in FAILURES:
            print(f"- {item}")
        return 1

    print("publication guard ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
