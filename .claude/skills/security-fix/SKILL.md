---
name: security-fix
description: >
  Audit existing code of the Opatrovatelky CRM for holes a logged-in employee can exploit by editing
  a request (changed ID, extra field, signal called by hand, hidden field, direct URL), then fix them
  with a proving test and record the result in the audit ledger. Takes a folder, file, class::method,
  URL, module name, topic (idor, permissions, autosave, upload, files, xss, sql, login, config, export),
  `diff`, `next`, `open`, `status` or `all`. Use whenever the user asks to check, audit, harden or patch
  security of some part of the app, says something "is full of holes" / "je to deravé", asks whether an
  employee could abuse a page, or invokes /security-fix, even when they name only a path or a URL.
argument-hint: "<folder|file|Class::method|URL|module|topic|diff|next|open|status|all> [--report] [--role=dealerJunior|dealer|ceo|guest] [--no-browser] [--force]"
---

# security-fix

Find what a logged-in employee can do that they should not, prove it, close it, and write down what was covered.

The security rules of the project are in the Security section of `CLAUDE.md`. This skill applies those rules to code that already exists. It does not restate them; read that section first if it is not in context.

## Who the attacker is

Assume a signed-in employee with the lowest role, `dealerJunior`, who has the browser console open. They can send any GET or POST, change any ID, add any field, call any signal with `?do=`, and type any URL. What the page shows or hides does not limit them. Then check a guest (no session), then a higher role reaching past its own permissions (a `dealer` changing accounts, a `ceo` raising themselves to `admin`). `--role=` moves the starting point.

Role rights come from one place: `AuthorizatorFactory::ROLE_RESOURCES`. Never infer what a role may do from a test, a database row or a menu.

Most records here are shared: everyone holding `Resource::BABYSITTER` may open every babysitter. So "someone else's record" usually means one of these, and each entity has to be checked for which apply:

- a child reached through the wrong parent (a document of family 7 addressed from the page of babysitter 3),
- a record of a module the role has no resource for (a `dealerJunior` reaching partners through search or a side panel),
- a record that does belong to one user (todo items, the own profile, account access),
- a sub-feature behind its own resource (`FAMILY_MANAGEMENT`, `WORKER_MANAGEMENT`, `TODO_VIEW_ALL`).

## Arguments

Arguments of this invocation: `$ARGUMENTS`

They hold one target and optional flags. With no target, behave as `status` and name the unit `next` would take.

| Target | Scope |
|---|---|
| folder (`app/Model/Service`) | every scannable file in it |
| file, `Class`, `Class::method` | that code and what it calls |
| URL (`http://opatrovatelky.local/babysitter/update/5?do=babysitterDocuments-delete`) | the presenter, action and signal behind it, every component the page creates including the ones `AdminPresenter` adds to all pages, their templates, forms, and the repository methods they reach |
| module (`babysitter`, `Admin:Turnus`) | `app/UI/Admin/<Module>/`, `app/UI/Admin/Control/<Module>/`, `app/UI/Admin/Form/<Module>/` and the repositories and services they use |
| topic (`idor`, `permissions`, `autosave`, `upload`, `files`, `xss`, `sql`, `login`, `config`, `export`) | one class of hole across the whole project; the matching section of the checklist says where to look |
| `diff`, `staged` | entry points touched by uncommitted or staged changes (`git diff --name-only`, `git diff --cached --name-only`) |
| `next` | the first unit `security-scan.php status` lists as not fully audited |
| `open` | findings in the ledger that are still open and already have the user's decision |
| `status` | print coverage and open findings, change nothing |
| `all` | the whole project unit by unit in the order of `status`; stop cleanly between units so `next` can resume |

| Flag | Effect |
|---|---|
| `--report` | change no application code and no tests; findings go to the ledger as open |
| `--role=<role>` | attacker role to start from (default `dealerJunior`, then guest) |
| `--no-browser` | skip the read-only browser check in URL mode |
| `--force` | re-audit files the ledger marks as audited and unchanged |

## Procedure

Work through the steps in order. Steps 3 and 4 are where an audit succeeds or fails: the common failure is not a wrong verdict but an entry point nobody looked at.

### 1. Resolve the scope

Turn the target into a list of files and say in one line what is in scope. [references/project-map.md](references/project-map.md) explains how a URL maps to a presenter, action, signal and component, which components sit on every page, and the order in which a presenter runs; read it before the first audit of a session.

The target names what is judged. Reading goes further, in both directions:

- **A control or a folder of controls**: grep `<Name>ControlFactory` to find every presenter that attaches it, and read how each one sets its context (`setContext()`, factory arguments) and on which actions. A control is only as safe as the weakest place it is attached.
- **Code that is not an entry point** (services, repositories, form factories, utilities): for each public method that takes an ID, an array of values, a column or a file name, find its callers with Grep and follow them up to the entry point that feeds it. The question is always what a request can put into that parameter.
- **Any entry point**: follow what it calls down to the query or the file operation.

Files read this way from outside the target are context. They are not audited by this run and are not marked as such in step 8.

State once per target which real roles hold the resource in question under `ROLE_RESOURCES`. When every role from `dealerJunior` up has it, "a role without the permission" exists only as a guest or as a test that denies the resource, and the interesting attacks are the ones on record identity and on written fields.

### 2. Read the ledger

Read `workflow/_active/security-audit/ANALYZA_A_WORKFLOW.md` if it exists (create it from the template in [references/fix-and-test.md](references/fix-and-test.md) on the first run). Take from it:

- **Rozhodnuté**: decisions the user has made. Do not report these again and do not propose changing them.
- open findings that touch the scope, so they are continued instead of duplicated,
- the highest `SEC-NNN` used so far.

### 3. Take the inventory

```bash
/c/wamp64/bin/php/php8.5.0/php.exe .claude/skills/security-fix/scripts/security-scan.php inventory <paths>
```

It lists every `action*`, `handle*`, `render*`, `createComponent*` and form event handler in scope with its line, parameters, the guard call found in its own body, the risky constructs inside it, and whether the file changed since it was last audited. Add `--summary` for a one-line-per-file overview of a large folder. Skip files reported as `audited <date>` unless `--force` is set.

Treat the table as the checklist to tick off, one row at a time. `**none**` in the guard column means "read this", not "this is a hole": the presenter's `startup()` may cover it, or a private helper with an unusual name may. A guard that is present may still check the wrong resource, and it says nothing about whether the record or the context is the right one.

The labels are leads the script can see from one file. It cannot see a hidden field whose value the handler trusts, a context that was never set, or a rule that lives in another class. Those come from reading.

### 4. Attack every entry point

For each row, read the code along the whole path: presenter `startup()` and `getResource()` → the handler → the service → the repository query. Then answer the four questions from `CLAUDE.md`:

1. Who can call it, and where on the server is that checked?
2. What happens when the ID is changed to another record?
3. What happens when an extra field, another context or another value is sent?
4. What happens when it is called directly by URL, without the UI?

Then go through the sections of [references/attack-checklist.md](references/attack-checklist.md) that match what the entry point does (reads a record, writes, handles a file, renders user data, returns JSON, builds SQL). The checklist is long because the classes of holes are many; read only the sections that apply.

Write a finding only after reading the code that proves it. A grep hit, a missing guard in the inventory or a suspicious name is a lead. If the path turns out safe, note in one line why and move on; that line is what lets the next run skip it with confidence.

Facts about the data often decide a severity: the width of a column, the values a field holds today, whether a suspicious row exists. Read-only queries against the development database (`opatrovatelky_nette`, port 3307) are fine for that. Never write to it. When the behaviour of the framework decides (what Latte escapes, what Nette refuses), check it in `vendor/` or by rendering a snippet instead of assuming; the installed versions are in `composer.lock`.

### 5. Record each finding

| Field | Content |
|---|---|
| ID | `SEC-NNN`, next free number in the ledger (re-read the ledger right before taking a number; other sessions write to it) |
| Závažnosť | Kritická / Vysoká / Stredná / Nízka, by the scale below |
| Miesto | `path/File.php:line` where the check, the validation or the log call belongs. When the cause sits in a file outside the scope (a value validated nowhere at its write side), give the in-scope line and name the outside one |
| Útok | what the employee does, in their terms: which page, which parameter, changed to what |
| Dopad | what they get: whose data, which change, which file |
| Oprava | the fix in one sentence, naming the pattern from `fix-and-test.md` |
| Stav | otvorený / opravený / čaká na rozhodnutie / mimo rozsahu |

Write filter and operator names without the pipe character (`filter noescape`), or the Markdown table of the ledger breaks.

Severity, judged for a signed-in `dealerJunior` unless the finding says otherwise. Grade by what the attacker gains, not by the class of the hole:

- **Kritická**: takes over an account or a role, runs code, reads or writes arbitrary files, reads or destroys data of the whole system.
- **Vysoká**: reads or changes records outside the role's resources, or another user's own records; deletes without permission.
- **Stredná**: a permitted role skips a rule that protects others (changes data without a trace, writes a value the UI and the whitelist do not allow), limited data leaks, or an attack that needs a condition the attacker does not control.
- **Nízka**: nobody else's data or rights are affected: a working request that only makes a mess of the attacker's own data, a missing second layer the project rules require, a rule of `CLAUDE.md` broken with no attack behind it today.

### 6. Fix

Skip this step with `--report`.

Order of work for each finding: write the test that performs the attack and watch it fail, apply the fix, watch it pass. A test that was never red proves nothing about the hole. Recipes for both halves, with the reference files to copy from, are in [references/fix-and-test.md](references/fix-and-test.md).

Decide per finding:

- **Clear** (the resource is evident from the presenter that owns the control, the record must be loaded through its parent, a column list must be explicit, a path must come from the database row): fix now.
- **Needs a decision** (who should be allowed is not derivable from `ROLE_RESOURCES`, the fix removes something users can do today, existing data would stop being valid, or the same fix belongs in sibling classes outside the scope and fixing one would make them diverge): leave the code, set the status to „čaká na rozhodnutie“, write the question with its options under „Čaká na rozhodnutie“ in the ledger, and ask with AskUserQuestion once the clear fixes of this run are done. Put the concrete options in the question, with what each one changes for whom. Record the answer on the „Rozhodnutie:“ line of that entry; `open` works from those lines.
- **Same hole in a sibling outside the scope** (the other `*DocumentsControl` classes, another presenter with the same handler): do not fix it. Record it with file and line as „mimo rozsahu“ and name it in the final report, saying for each whether the code was read or only found by search. `CLAUDE.md` forbids silent rewrites at scale, and the user chose the scope.
- **Same fix needed twice inside the scope**: extract it into a shared service, trait or base class instead of copying it. Look in `app/Model/Service`, `app/Model/Utils` and `app/Model/Repository` for something that already does the job first.

A fix must not weaken an existing check, and must not take a function away from a role that has it under `ROLE_RESOURCES`. Sensitive changes it touches are logged through `ChangeAuditLogger`.

### 7. Verify

With `--report` nothing changed, so skip the checks below and say so in the report.

- PHPStan level 8 with the PHP 8.5 binary: `/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/phpstan analyse --level 8 --memory-limit=512M`
- the new tests alone, then the whole suite: `/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/phpunit --filter <TestClass>` and then without `--filter`
- if a `.latte` file changed: `vendor/bin/latte-lint app/UI` and a test that really renders the template (`renderPresenter()` from `PresenterWorkflowTrait`); lint alone does not show that a page renders
- if a `.neon` file changed: `vendor/bin/neon-lint config`

URL mode without `--no-browser` adds a read-only check in Chrome. Open the page in a new tab and ask the user to sign in there themselves; never ask for a password. Send only GET requests: the URL with another ID, the action without its parent page, a download signal with a foreign document ID. Do not submit forms, call deleting or writing signals, or upload anything in the browser; those attacks are proven by the tests of step 6, where the database is a throwaway one.

Report results as they are. A failing test or a PHPStan error is stated with its output, not smoothed over.

### 8. Write the ledger and stage

Update `workflow/_active/security-audit/ANALYZA_A_WORKFLOW.md`: the coverage row of each unit, the findings table, the decisions waiting for the user, one dated line in „Stav prác“. Other sessions edit the same file, so re-read it and change only your own lines; never rewrite a section wholesale.

Mark as audited the files **in scope** in which every entry point was attacked and no finding is left open or waiting, including the ones with nothing to find (the inventory names those in its last line):

```bash
/c/wamp64/bin/php/php8.5.0/php.exe .claude/skills/security-fix/scripts/security-scan.php mark <files>
```

Do not mark a file with an open or waiting finding; it has to come up again. Do not mark files outside the scope that were read as context: they were seen from one angle only, and marking them would make `next` skip entry points nobody attacked.

When the target was part of a unit, write the coverage row as partial (`3 z 15 súborov`).

Stage your own changes in `app/`, `config/`, `tests/` and `www/` with `git add`. Never stage `private/`. Do not commit unless asked. `workflow/` is git-ignored and stays local.

### 9. Report

Answer in Slovak, conclusion first, no filler. Use this shape:

```
Prešiel som <rozsah>: <N> vstupných bodov, <N> nálezov.

| ID | Závažnosť | Miesto | Útok | Stav |
|---|---|---|---|---|

Opravené: <čo a ktorým testom je to dokázané>
Čaká na rozhodnutie: <otázka pre každý nález>
Mimo rozsahu: <súbor:riadok, rovnaká diera; prečítané alebo len nájdené vyhľadávaním>
Overené ako bezpečné: <vstupný bod a jednou vetou prečo>
Chýbajúce testy: <vstupný bod bez testu na cudzie ID, chýbajúce právo alebo hosťa>
Kontroly: PHPStan <výsledok>, testy <počet, výsledok>
Ďalej: <jednotka, ktorú navrhuje status>
```

Give file references as `path:line`. Leave out a row of the summary that is empty. „Overené ako bezpečné“ is what lets the user tell a thorough result from a shallow one, so keep it even when there are findings; when nothing was found, say so in one sentence and name what was read.

## Large scopes

For `all`, a topic, or a folder with more than about 15 files with entry points: split by the units `status` prints, finish one unit completely (steps 3 to 8) before starting the next, and write the ledger after each. An interrupted run then loses one unit at most, and `next` picks up where it stopped.
