from __future__ import annotations

import re
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


def main() -> int:
    config = read("services/config.php")
    bootstrap = read("services/app_bootstrap.php")
    login = read("services/login.php")
    create_player = read("services/create_player.php")
    membership = read("services/submit_membership_application.php")
    membership_download = read("services/download_membership_application.php")
    membership_htaccess = read("files/applications/membership/.htaccess")
    sql = read("dart_club.sql")
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

    if FAILURES:
        print("publication guard failed:")
        for item in FAILURES:
            print(f"- {item}")
        return 1

    print("publication guard ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
