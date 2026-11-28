// EXAMPLE ONLY: copy to tmp/e2e/, then adapt URLs, selectors and assertions to the project/scenario.
// Run from the repo root: node tmp/e2e/login.example.mjs
import { chromium } from 'playwright-core';
import { existsSync, readFileSync } from 'node:fs';

const BASE = 'http://localhost:8089';
const STAGE = 'admin'; // 'admin' | 'front'
const COOKIE = { admin: 'WINDWALKER_ADMIN_SESSID', front: 'WINDWALKER_SESSID' }[STAGE];
const LOGIN_URL = { admin: '/admin/login', front: '/login' }[STAGE];
const ENV_FILE = '.env.agent.local';

if (!existsSync(ENV_FILE)) {
  // Ask the user for credentials or a session ID, then write the file (see ../e2e-windwalker-login.md).
  throw new Error(`${ENV_FILE} not found`);
}
const env = Object.fromEntries(
  readFileSync(ENV_FILE, 'utf8')
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line && !line.startsWith('#') && line.includes('='))
    .map((line) => {
      const i = line.indexOf('=');
      return [line.slice(0, i).trim(), line.slice(i + 1).trim()];
    }),
);
const auth = {
  session_id: env[`TEST_${STAGE.toUpperCase()}_SESSION_ID`] ?? '',
  username: env[`TEST_${STAGE.toUpperCase()}_USERNAME`] ?? '',
  password: env[`TEST_${STAGE.toUpperCase()}_PASSWORD`] ?? '',
};

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext();
const page = await context.newPage();

const dialogs = [];
page.on('dialog', (d) => { dialogs.push(d.message()); d.dismiss(); }); // native alert/confirm

if (auth.session_id) {
  await context.addCookies([{ name: COOKIE, value: auth.session_id, domain: 'localhost', path: '/' }]);
}

await page.goto(`${BASE}/admin/article/list`);

// Not logged in (session missing/expired): fall back to username + password.
if (page.url().includes('/login')) {
  if (!auth.username || !auth.password) {
    throw new Error(`Session invalid and no USERNAME/PASSWORD for ${STAGE} in ${ENV_FILE}; ask the user.`);
  }
  await page.goto(`${BASE}${LOGIN_URL}`);
  await page.fill('#input-user-email', auth.username);
  await page.fill('#input-user-password', auth.password);
  await Promise.all([page.waitForLoadState('load'), page.click('#login-form .login-button')]);
  await page.goto(`${BASE}/admin/article/list`);
}

await page.waitForLoadState('networkidle');

// Scenario: click delete with nothing checked.
await page.click('button:has(.fa-trash)');
await page.waitForTimeout(1500);

console.log('checked:', await page.locator('input[data-role=grid-checkbox]:checked').count());
console.log('native dialogs:', dialogs);

await browser.close();
