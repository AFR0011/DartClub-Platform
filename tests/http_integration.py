from __future__ import annotations

import contextlib
import http.cookiejar
import json
import os
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PASSWORD = "Portfolio-CI-Password-42"


def fail(message: str) -> None:
    raise AssertionError(message)


def request_json(
    opener: urllib.request.OpenerDirector,
    method: str,
    path: str,
    payload: dict[str, object] | None = None,
    port: int = 8090,
) -> tuple[int, object]:
    data = None
    headers: dict[str, str] = {}
    if payload is not None:
        data = json.dumps(payload).encode("utf-8")
        headers["Content-Type"] = "application/json"
    request = urllib.request.Request(
        f"http://127.0.0.1:{port}{path}",
        data=data,
        method=method,
        headers=headers,
    )
    try:
        response = opener.open(request, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    body = response.read().decode("utf-8", errors="replace")
    try:
        decoded: object = json.loads(body)
    except json.JSONDecodeError:
        decoded = body
    return response.status, decoded


@contextlib.contextmanager
def php_server(environment: dict[str, str], port: int):
    creation_flags = subprocess.CREATE_NO_WINDOW if os.name == "nt" else 0
    with tempfile.TemporaryDirectory(prefix="dartclub-http-") as temp_dir:
        log_path = Path(temp_dir) / "php-server.log"
        with log_path.open("w", encoding="utf-8") as log:
            process = subprocess.Popen(
                ["php", "-S", f"127.0.0.1:{port}", "-t", str(ROOT)],
                cwd=ROOT,
                env=environment,
                stdout=log,
                stderr=subprocess.STDOUT,
                creationflags=creation_flags,
            )
            try:
                deadline = time.monotonic() + 20
                while time.monotonic() < deadline:
                    if process.poll() is not None:
                        fail(f"PHP server exited early:\n{log_path.read_text(errors='replace')}")
                    try:
                        with socket.create_connection(("127.0.0.1", port), timeout=1):
                            break
                    except OSError:
                        time.sleep(0.2)
                else:
                    fail("PHP server did not accept connections in time.")
                yield
            finally:
                process.terminate()
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait(timeout=5)


def cookie_opener() -> tuple[urllib.request.OpenerDirector, http.cookiejar.CookieJar]:
    jar = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar)), jar


def login(email: str) -> urllib.request.OpenerDirector:
    opener, jar = cookie_opener()
    status, body = request_json(
        opener,
        "POST",
        "/services/login.php",
        {"email": email, "password": PASSWORD},
    )
    if status != 200 or not isinstance(body, dict) or body.get("success") is not True:
        fail(f"Login failed for {email}: status={status}, body={body!r}")
    cookie_names = {cookie.name for cookie in jar}
    if cookie_names != {"dart_club_session"}:
        fail(f"Expected only dart_club_session, received {sorted(cookie_names)}")
    return opener


def find_user(users: object, email: str) -> dict[str, object]:
    if not isinstance(users, list):
        fail(f"Expected a user list, received {users!r}")
    for user in users:
        if isinstance(user, dict) and user.get("email") == email:
            return user
    fail(f"User fixture not returned: {email}")


def main() -> int:
    environment = os.environ.copy()
    with php_server(environment, 8090):
        guest, _ = cookie_opener()
        status, body = request_json(guest, "GET", "/services/get_users.php")
        if status != 403 or not isinstance(body, dict) or body.get("success") is not False:
            fail(f"Guest user listing was not denied cleanly: {status}, {body!r}")

        status, body = request_json(
            guest,
            "GET",
            "/services/download_membership_application.php?application_id=1",
        )
        if status != 403 or body != "Forbidden":
            fail(f"Guest membership download was not denied: {status}, {body!r}")

        admin = login("portfolio-ci-admin@local.test")
        status, users = request_json(admin, "GET", "/services/get_users.php")
        if status != 200:
            fail(f"Authenticated user listing failed: {status}, {users!r}")
        role_target = find_user(users, "portfolio-ci-role-target@local.test")
        role_target_id = int(role_target["user_id"])

        status, body = request_json(
            admin,
            "POST",
            "/services/update_user_role.php",
            {"user_id": role_target_id, "new_role": "manager"},
        )
        if status != 200 or not isinstance(body, dict) or body.get("success") is not True:
            fail(f"Admin role update failed: {status}, {body!r}")

        status, users = request_json(admin, "GET", "/services/get_users.php")
        updated = find_user(users, "portfolio-ci-role-target@local.test")
        if updated.get("user_role") != "manager" or updated.get("membership_status") != "not_submitted":
            fail(f"Role update crossed the membership boundary: {updated!r}")

        player = login("portfolio-ci-member@local.test")
        status, body = request_json(
            player,
            "POST",
            "/services/update_user_role.php",
            {"user_id": role_target_id, "new_role": "admin"},
        )
        if status != 403:
            fail(f"Player role mutation was not denied: {status}, {body!r}")

        status, applications = request_json(
            admin, "GET", "/services/get_membership_applications.php"
        )
        if status != 200 or not isinstance(applications, dict):
            fail(f"Membership application listing failed: {status}, {applications!r}")
        fixture = next(
            (
                item
                for item in applications.get("items", [])
                if item.get("original_filename") == "portfolio-ci-synthetic.pdf"
            ),
            None,
        )
        if not fixture:
            fail("Synthetic membership application was not returned.")

        status, body = request_json(
            admin,
            "POST",
            "/services/review_membership_application.php",
            {
                "application_id": int(fixture["application_id"]),
                "decision": "approve",
                "reviewer_notes": "Synthetic integration approval",
            },
        )
        if status != 200 or not isinstance(body, dict) or body.get("membership_status") != "approved":
            fail(f"Membership approval failed: {status}, {body!r}")

        status, users = request_json(admin, "GET", "/services/get_users.php")
        member = find_user(users, "portfolio-ci-member@local.test")
        if member.get("membership_status") != "approved" or member.get("user_role") != "player":
            fail(f"Membership review crossed the auth-role boundary: {member!r}")

    broken_environment = environment.copy()
    broken_environment["APP_ENV"] = "production"
    broken_environment["APP_DB_NAME"] = "portfolio_ci_missing_database"
    with php_server(broken_environment, 8091):
        opener, _ = cookie_opener()
        status, body = request_json(opener, "GET", "/services/get_users.php", port=8091)
        if status != 500 or not isinstance(body, dict):
            fail(f"Production failure did not use JSON/500: {status}, {body!r}")
        if body.get("message") != "Unexpected server error.":
            fail(f"Production response disclosed unexpected details: {body!r}")

    print("HTTP integration contracts ok")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except AssertionError as error:
        print(f"FAIL: {error}", file=sys.stderr)
        raise SystemExit(1)
