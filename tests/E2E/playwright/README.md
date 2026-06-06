# Browser E2E (Playwright) — optional

This is an **optional** real-browser layer. It is not run by PHPUnit and not part of CI by default.
The in-process workflow coverage lives in `tests/E2E/AdminWorkflowE2ETest.php` and runs without a browser.

## Prerequisites

- The app is running and reachable (default `http://opatrovatelky.local`).
- A known user exists with active = 1 and a password you control.

## Install

```bash
cd tests/E2E/playwright
npm install
npx playwright install chromium
```

## Run

```bash
# from tests/E2E/playwright
E2E_BASE_URL=http://opatrovatelky.local E2E_USER=admin@example.test E2E_PASSWORD='secret123!' npm test
```

On Windows PowerShell:

```powershell
$env:E2E_USER='admin@example.test'; $env:E2E_PASSWORD='secret123!'; npm test
```

## Notes

- Login form selectors target placeholders `Email` / `Heslo` and the `Prihlásiť` button
  (see `app/UI/Login/Control/Login/templates/LoginControl.latte`). Update the spec if the form changes.
- Seed/teardown of test data is the runner's responsibility — these tests assume the credentials above
  already work against the target environment.
