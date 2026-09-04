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
        ["git", "ls-files"], cwd=ROOT, check=True, capture_output=True, text=True
    )
    return [line.strip().replace("\\", "/") for line in result.stdout.splitlines() if line.strip()]


def main() -> int:
    config = read("services/config.php")
    bootstrap = read("services/app_bootstrap.php")
    login = read("services/login.php")
    get_users = read("services/get_users.php")
    update_role = read("services/update_user_role.php")
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
    license_text = read("LICENSE")
    read("services/config.local.example.php")
    read("tests/prepare_integration_fixture.php")
    read("tests/tournament_contracts.php")
    read("tests/http_integration.py")
    read("docs/assets/dartclub-architecture.svg")
    read("docs/assets/dartclub-homepage.png")

    password_default = re.search(
        r"app_config_env\(['\"]APP_DB_PASS['\"],\s*['\"]([^'\"]*)['\"]\)",
        config,
    )
    if password_default is None or password_default.group(1) != "":
        fail("services/config.php contains a non-empty default DB password")

    for marker in (
        "session_name('dart_club_session')",
        "session.use_strict_mode",
        "session.use_only_cookies",
        "'httponly' => true",
        "'samesite' => 'Lax'",
        "app_enforce_same_origin_mutation",
        "HTTP_ORIGIN",
    ):
        if marker not in bootstrap:
            fail(f"bootstrap security marker missing: {marker}")

    if "session_regenerate_id(true)" not in login:
        fail("login does not rotate the session identifier")

    for name, text in (("get_users.php", get_users), ("update_user_role.php", update_role)):
        if "'/auth.php'" not in text or "app_json_response" not in text:
            fail(f"{name} bypasses the canonical auth/JSON path")
        if re.search(r"\bsession_start\s*\(", text):
            fail(f"{name} starts a default PHP session directly")

    if "Temporary password:" in create_player or "temporary password: {$rawPassword}" in create_player:
        fail("create_player.php sends a temporary password by email")

    if "finfo_file" not in membership or "@unlink($destination)" not in membership:
        fail("membership upload MIME/rollback controls are incomplete")
    if "is_manager_or_admin()" not in membership_download:
        fail("membership download endpoint is not manager/admin-gated")
    if "Require all denied" not in membership_htaccess:
        fail("membership upload directory is not blocked from direct Apache access")

    for marker in ("DOMDocument", "app_safe_rich_html_url", "noopener noreferrer", "allowedAttributes"):
        if marker not in sanitizer:
            fail(f"rich HTML sanitizer marker missing: {marker}")
    if "mb_convert_encoding" in sanitizer:
        fail("rich HTML sanitizer retains the PHP 8.4-deprecated HTML-entity conversion")
    for name, text in (("create_blog.php", create_blog), ("get_blogs.php", get_blogs)):
        if "shared/html_sanitizer.php" not in text or "app_sanitize_rich_html" not in text:
            fail(f"{name} does not use the shared rich HTML sanitizer")

    for password in ("adminpass", "managerpass", "memberpass", "playerpass"):
        if re.search(rf"['\"]{re.escape(password)}['\"]", sql):
            fail(f"dart_club.sql stores a local seed password in plaintext: {password}")

    raw_exception = re.compile(r"\$[A-Za-z_][A-Za-z0-9_]*->getMessage\s*\(")
    for path in (ROOT / "services").rglob("*.php"):
        if path.name == "app_bootstrap.php":
            continue
        if raw_exception.search(path.read_text(encoding="utf-8", errors="replace")):
            fail(f"raw exception message used in service: {path.relative_to(ROOT)}")

    for marker in (
        "controlled deployment",
        "MariaDB-backed integration",
        "Ali Farrokhnejad authored and maintains",
        "same repository and normal commits",
        "MIT License",
    ):
        if marker not in readme:
            fail(f"README missing release marker: {marker}")
    normalized_security = " ".join(security.split())
    normalized_publication = " ".join(publication.split())
    if "local demonstration and controlled deployment" not in normalized_security:
        fail("SECURITY.md does not state the supported deployment boundary")
    if "same repository name and authentic development history" not in normalized_publication:
        fail("PUBLICATION.md does not state the approved history boundary")
    if '"license": "MIT"' not in composer:
        fail("composer.json must declare MIT")
    if not license_text.startswith("MIT License") or "Copyright (c) 2026 Ali Farrokhnejad" not in license_text:
        fail("LICENSE must contain the expected MIT text and copyright notice")

    tracked = tracked_files()
    forbidden_prefixes = ("vendor/", ".codex/", ".codex-observer/", ".claude/", "__pycache__/")
    for path in tracked:
        if path.startswith(forbidden_prefixes) or path.endswith((".pyc", ".log")):
            fail(f"generated/tool state is tracked: {path}")

    forbidden_files = {
        "progress.md",
        "Manual Verification.txt",
        "Finalization Options.txt",
        "todolist.md",
        "tournament-errors.md",
        "pages/developers.html",
        "pages/admin/manage_blogs.php",
        "pages/admin/image_upload.php",
        "pages/admin/view_match_details.php",
        "pages/admin/record_match_result.php",
    }
    for path in sorted(forbidden_files.intersection(tracked)):
        fail(f"retired publication surface remains tracked: {path}")

    public_files = [ROOT / "README.md", ROOT / "PUBLICATION.md", ROOT / "SECURITY.md", ROOT / "AGENTS.md"]
    public_files.extend((ROOT / "docs").rglob("*.md"))
    forbidden_phrases = (
        "C:\\Users\\Ali\\",
        "LinkedIn URL pending",
        "Personal logo slot reserved",
        "Studio logo placeholder",
        "clean modern history",
        "clean-history public repository",
    )
    for path in public_files:
        text = path.read_text(encoding="utf-8", errors="replace")
        for phrase in forbidden_phrases:
            if phrase in text:
                fail(f"stale/private publication phrase in {path.relative_to(ROOT)}: {phrase}")

    if FAILURES:
        print("publication guard failed:")
        for item in FAILURES:
            print(f"- {item}")
        return 1

    print("publication guard ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
