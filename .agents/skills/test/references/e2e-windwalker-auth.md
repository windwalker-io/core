# E2E: Windwalker login and authentication methods

This document explains how to obtain and inject login state when running Playwright E2E tests in Windwalker projects. For the general E2E workflow, see `e2e-testing.md`.

## Choose a login method

Before the first test that requires login:

1. **First, try `.env.agent.local`.** If it already contains a usable entry for this site (a still-valid `TEST_<STAGE>_SESSION_ID`, or `TEST_<STAGE>_USERNAME` + `TEST_<STAGE>_PASSWORD`), use it without asking.
2. Otherwise (the file does not exist, the site's fields are empty, or the entry is invalid), **ask the user which method to use** (use the ask-user tool, one question at a time):
   - **Username + password** → write to `.env.agent.local` and log in through the form.
   - **Session ID** → write to `.env.agent.local` and inject it as a cookie.
   - **Session bridge** (forge a session for an existing user through the database; requires database access from `phpunit.xml` / `.env`) → see “Option: session bridge”.
   - **Temporary test user** (last resort) → see “Fallback: temporary test user”.
3. Never display credentials in a response or copy them into instructions, skills, documentation, or memory.

## Credential file: `.env.agent.local`

Store test login state in `.env.agent.local` at the repository root (ignored by Git via `.env*.local`). This is a dotenv file (one `KEY=value` per line) that users can edit at any time.

```dotenv
TEST_FRONT_USERNAME=
TEST_FRONT_PASSWORD=
TEST_FRONT_SESSION_ID=
TEST_ADMIN_USERNAME=
TEST_ADMIN_PASSWORD=
TEST_ADMIN_SESSION_ID=
```

Rules:

1. If the user chooses username/password or a Session ID, write only that item to the corresponding site's entry (`TEST_ADMIN_*` or `TEST_FRONT_*`). Never invent credentials.
2. Read this file before later runs. If `TEST_<STAGE>_SESSION_ID` is set, prefer it; otherwise log in with `TEST_<STAGE>_USERNAME` and `TEST_<STAGE>_PASSWORD`.
3. If the saved Session ID has expired (the browser is redirected to the login page), use the username/password if available; otherwise ask the user again and update the file.
4. Do not read cookies or settings from the user's browser. Use only data provided by the user.

### Session cookie names

| Site | Cookie name | Login URL |
| --- | --- | --- |
| Frontend | `WINDWALKER_SESSID` | `/login` (varies by site) |
| Backend | `WINDWALKER_ADMIN_SESSID` | `/admin/login` |

Before opening a page, inject the Session ID with `context.addCookies([{ name, value, domain: 'localhost', path: '/' }])`. Cookies are shared across different ports on `localhost`, but the session must exist in the same session storage used by the server.

### Login forms

In most cases, you can refer to this login page structure, but adjust or ask if the project has a custom login page:

- Frontend: fields `#input-user-email` and `#input-user-password`; submit button `#login-form .login-button`; redirects to `/` on success.
- Backend: fields `#input-user-email` and `#input-user-password`; submit button `#login-form .login-button`; redirects to `/admin` on success.
- Other projects may differ; inspect the page before reusing selectors.

### Login script example

`../assets` demonstrates logging in with a Session ID or credentials and entering the backend. It is an example only; adjust it for the project.

## Quick check with curl

`curl` is suitable for checking that a page returns 200 (rather than redirecting to the login page), but note:

- **Send a browser User-Agent.** `SessionRobotSubscriber` disables sessions for bot user agents, so the default `curl/x.x` UA will be treated as unauthenticated even with a valid session cookie (verified: adding `-A` changes the response from 303 to 200).
- To avoid being treated as a bot, use a real browser's UA, for example:
  - Chrome: `Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36`
  - Edge: `Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36 Edg/120.0`
- Cookie attributes (domain, path, httponly, samesite) do not affect `-b "NAME=value"`; it sends a raw header.
- In PowerShell, use `curl.exe`; when accessing `/admin/*`, use the backend cookie name `WINDWALKER_ADMIN_SESSID`.

```bash
curl -s -o NUL -w "%{http_code} %{redirect_url}`n" `
  -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36" `
  -b "WINDWALKER_ADMIN_SESSID=$id" http://localhost:8089/admin/article/list
```

Use Playwright for clicks, dialogs, and JS behavior.

## Option: session bridge (forge a session for an existing user)

Use when the user cannot provide a Session ID and does not want to log in with a password. `../assets` writes a session row for an **existing** user (never changes the password), outputs its ID, and deletes it afterward.

1. Copy it to `tmp/e2e/session-bridge.php` and adjust as needed (root path, database driver, user email).
2. `php tmp/e2e/session-bridge.php create <email> admin` → get the session ID; put it in a cookie (use `WINDWALKER_ADMIN_SESSID` for the backend), or set `TEST_<STAGE>_SESSION_ID` in `.env.agent.local`.
3. When finished: run `php tmp/e2e/session-bridge.php destroy <session_id>`, clear the value of `TEST_<STAGE>_SESSION_ID` in `.env.agent.local`, and delete the copied script.

Verified details: storage follows `SESSION_DEFAULT` (`.env.base` sets it to the `sessions` database table, with columns `id, data, user_id, stage, time`; by default it is `<tmp>/sess/sess_<id>`). The data is `serialize(['login_user_id' => <int>])`; also set `user_id` and `stage` (`admin` / `front`). The user must have `enabled=1`, `verified=1`, and a superuser/admin role. Database settings come from `WINDWALKER_TEST_DB_DSN` in `phpunit.xml` (preferred; parse host / dbname / user / password), falling back to `DATABASE_*` from `.env.base` / `.env.dev` / `.env`. Confirm that the DSN's `dbname` is the application database, not the framework's test database. Never print the password. The forged cookie works in Playwright / Chrome; plain `curl` is redirected to the login page, so verify in a browser.

## Fallback: temporary test user

If the user cannot provide credentials or a session, create a temporary user in the database (connection details from the user's `phpunit.xml` / `.env`, for connection only; never print the password). Delete it afterward; do not modify an existing backend administrator's password hash. Requirements:

- Add a `user_roles` row with the **superuser** role (preferred over `admin` because permissions vary widely by project). At least `admin` or `superuser` is required to enter the backend.
- `enabled` must be `1`; `verified` must be `1`.
- A newly created user may not have its own data (profile, related records); prepare the data required by the page.
- Generate the password hash at runtime with PHP (`password_hash`); do not store a fixed hash in the repository.
- Write the new account to `.env.agent.local` like other credentials (`TEST_<STAGE>_USERNAME` / `TEST_<STAGE>_PASSWORD`), then remove the user when finished.

## Related documentation

- [Session component](https://windwalker.io/documentation/components/session/index.md)
- [Session handlers](https://windwalker.io/documentation/components/session/handlers.md)
- [Session bridges](https://windwalker.io/documentation/components/session/bridges.md)
- [Sessions and cookies](https://windwalker.io/documentation/framework/core-concepts/state-control/session-and-cookies.md)
