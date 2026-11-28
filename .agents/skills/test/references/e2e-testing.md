# E2E / Playwright testing guide

## Purpose

Use a real browser to verify frontend behavior that requires interaction (table toolbar buttons, dialogs, forms) against a local development server.

For Windwalker flows that require login (credential file, session cookie, session bridge, etc.), see `e2e-windwalker-auth.md`.

## Prerequisites

- Run tests with `playwright-core`. It does not download browsers; launch an installed Chrome with `channel: 'chrome'` (or Edge with `channel: 'msedge'`). If the package is missing, notify the user.
- Put scripts and logs in `tmp/e2e/` (ignored by Git). Run from the repository root: `node tmp/e2e/<script>.mjs`.
- If a PHP dev server or JS dev server must be started, or JS must be built (`yarn build`) before testing, search the instructions for the project's procedure.

## Workflow

- **Start the PHP server on an explicit port** and record its PID:

   ```bash
   php windwalker server:start php:main --host localhost --port 8089 \
     > tmp/e2e/server.out 2> tmp/e2e/server.err &
   echo $!                                                        # server PID
   curl -s -o /dev/null -w "%{http_code}" http://localhost:8089/  # expect 200
   ```

   The returned PID may belong to a launcher rather than the process actually listening. Use `Get-NetTCPConnection -LocalPort 8089 -State Listen` to find the real PID, and stop only that process (`Stop-Process -Id <PID>`).
- **Prepare login (if needed)**: see `e2e-windwalker-auth.md`.
- **Copy and adjust the script**: copy `../assets` to `tmp/e2e/`, adjust it for the project and test scenario, then run it.
- **Cleanup**: delete the `.mjs` script and logs, and stop only the server you started. Keep `.env.agent.local`.

## Example script

`../assets` logs in (using a Session ID or credentials from `.env.agent.local`), opens a backend list page, clicks the delete toolbar button without selecting any items, and outputs the native dialog content. **This is only an example: adjust the URL, selectors, and assertions for the project and test scenario.**
