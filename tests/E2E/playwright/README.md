# Browser E2E (Playwright) — optional

This is an **optional** real-browser layer. It is not run by PHPUnit and not part of CI by default.
The in-process workflow coverage lives in `tests/E2E/AdminWorkflowE2ETest.php` and runs without a browser.

## Prerequisites

- The app is running and reachable (default `http://opatrovatelky.local`). On WAMP this means
  Apache is up and the vhost resolves; verify with `curl -I http://opatrovatelky.local`.
- A known user exists with `active = 1` and a password you control (see "Seed a login user").

## Seed a login user

The password column stores a bcrypt hash, so insert a row with a hash you generate yourself.

1. Generate a hash for your chosen password:

   ```bash
   /c/wamp64/bin/php/php8.5.0/php.exe -r "echo password_hash('secret123!', PASSWORD_DEFAULT), PHP_EOL;"
   ```

2. Insert the user into the dev database `opatrovatelky_nette` (replace `<HASH>`):

   ```sql
   INSERT INTO sany_users (name, second_name, acronym, email, password, permission, color, active)
   VALUES ('E2E', 'Admin', 'E2', 'admin@example.test', '<HASH>', 10, '#8A2062', 1);
   ```

   `permission = 10` is ADMIN. Use the same email/password as `E2E_USER` / `E2E_PASSWORD` below.

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
