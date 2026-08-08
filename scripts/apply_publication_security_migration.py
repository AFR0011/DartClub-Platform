from __future__ import annotations

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]

SEED_HASHES = {
    "adminpass": "$2y$12$34vBtdj5ZjmmgN56Qp2Fqed/Kwg1x0cwcF8kAcjZiJFmg07HzmZia",
    "managerpass": "$2y$12$iMVa5WDKHbpg98T/Wvpb1ui6jZMBBi.U9TGDQ1XslitgFJ3J2btNC",
    "memberpass": "$2y$12$T2n8918KXu3MYT2XbjHCsOKKJNArfMP0.tW.EUgUKJbuc6iluezKq",
    "playerpass": "$2y$12$eIJfOw9pFguU3JrSgPGiRu9tW506HjvJ8gOJ./ORjcVnZIRVAot9a",
}


def migrate_service_errors() -> int:
    changed = 0
    for path in sorted((ROOT / "services").rglob("*.php")):
        if path.name == "app_bootstrap.php":
            continue
        text = path.read_text(encoding="utf-8")
        if "$exception->getMessage()" not in text:
            continue
        if "app_bootstrap.php" not in text and "dbConnection.php" not in text:
            raise SystemExit(f"refusing to rewrite exception handling without shared bootstrap: {path.relative_to(ROOT)}")
        updated = text.replace("$exception->getMessage()", "app_safe_error_message($exception)")
        path.write_text(updated, encoding="utf-8")
        changed += 1
        print(f"hardened exception output: {path.relative_to(ROOT)}")
    return changed


def migrate_seed_passwords() -> int:
    path = ROOT / "dart_club.sql"
    text = path.read_text(encoding="utf-8")
    changed = 0
    for plaintext, hashed in SEED_HASHES.items():
        old = f"'{plaintext}'"
        new = f"'{hashed}'"
        if old in text:
            text = text.replace(old, new)
            changed += 1
            print(f"hashed local seed credential: {plaintext}")
        elif new in text:
            print(f"seed credential already hashed: {plaintext}")
        else:
            raise SystemExit(f"expected seed credential not found: {plaintext}")
    path.write_text(text, encoding="utf-8")
    return changed


def main() -> None:
    service_count = migrate_service_errors()
    seed_count = migrate_seed_passwords()
    print(f"migration complete: {service_count} service files hardened, {seed_count} seed passwords replaced")


if __name__ == "__main__":
    main()
