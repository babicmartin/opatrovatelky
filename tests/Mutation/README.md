# Mutation testing (Infection)

Mutation testing runs **separately** from the default PHPUnit suite. It mutates source
code and re-runs the covering tests to find assertions that never fail ("escaped mutants").
It is slow, so it is **scoped to the highest-risk classes only** — never the whole app.

## Scope

Configured in `infection.json5` (repo root):

- `app/Model/Service/Autosave/AutosaveFieldUpdateService.php`
- `app/Model/Repository/ChangeLogRepository.php`
- `app/Model/Repository/SecurityAuditLogRepository.php`
- `app/Model/Security/Authorizator/AuthorizatorFactory.php`

`infection.json5` lists the parent directories as the source universe; narrow each run to
the exact files with `--filter` (see commands below).

## Prerequisites

1. **A code coverage driver** — Infection needs line coverage. Either:
   - Xdebug built for the active PHP (PHP 8.5), or
   - PCOV.
   > The current dev box ships `php_xdebug-3.4.7-8.4-ts` which does **not** load under PHP 8.5
   > (`No code coverage driver available`). Install a matching Xdebug or PCOV before running.
2. **The test database** — the repository mutants are covered by integration tests, so the
   same `TEST_DATABASE_DSN` (a `*_test` DB) used by PHPUnit must be reachable.

## Commands

Run the full scoped config:

```bash
/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/infection --threads=max --show-mutations
```

Run a single class (fastest feedback):

```bash
/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/infection \
  --filter=app/Model/Repository/ChangeLogRepository.php --threads=max
```

Run only the four target files:

```bash
/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/infection --threads=max \
  --filter=app/Model/Service/Autosave/AutosaveFieldUpdateService.php,app/Model/Repository/ChangeLogRepository.php,app/Model/Repository/SecurityAuditLogRepository.php,app/Model/Security/Authorizator/AuthorizatorFactory.php
```

## Output

Reports are written to `temp/infection/` (gitignored): `infection.log` (escaped mutants),
`summary.log` and `infection.html`. Review escaped mutants and add assertions that kill them.
