---
name: opatrovatelky-old-web-migration
description: Migrate pages and features from the legacy Opatrovatelky CRM at C:\wamp64\www\monika\opatrovatelky\old into this Nette 3.2 / PHP 8.5 CRM project. Use when the user asks to migrate, port, convert, rebuild, preniesť, prerobiť, or analyze an old page such as homepage, opatrovatelky, families, partneri, turnus, projekty, opatrovanie, todo, dokumenty, proposal-records, settings, translation, user-management, or missing-registry.
---

# Opatrovatelky Old Web Migration

## Project Context

Work in the new app at `C:\wamp64\www\monika\opatrovatelky\local`. The legacy app is at `C:\wamp64\www\monika\opatrovatelky\old`.

The new app is a Nette 3.2+ CRM on PHP 8.5 with Login and Admin modules. Admin requires authentication. Existing conventions matter more than generic Nette examples.

Use English domain names in new code even when legacy pages are Slovak:

| Legacy name | New domain |
| --- | --- |
| opatrovatelky | Babysitter |
| families, rodiny | Family |
| partneri | Partner |
| turnus | Turnus unless the existing codebase has moved the feature to Rotation |
| opatrovanie | Care |
| pracovnici | Employee |
| dokumenty | Document |
| proposal, navrhy | Proposal |

Before making changes, read only the references needed for the page:

- `references/old-web-map.md` for legacy page locations, tab maps, AJAX endpoints, and table names.
- `references/project-conventions.md` for Nette project layout and implementation conventions.
- `references/migration-patterns.md` for old-to-new translation patterns.

## Migration Workflow

1. Identify the requested legacy page and map it in `references/old-web-map.md`.
2. Read the old controller, template, update controller/template, subtemplates, JavaScript snippets, and model class if they exist.
3. Inspect the new project for existing TableMap, Entity, Factory, Repository, Form, Control, Presenter, ACL, route, and config pieces. Prefer extending what exists.
4. Extract behavior from the old page:
   - list columns, filters, sorting, pagination, row actions
   - detail tabs and field groups
   - inline edits and modal forms
   - AJAX endpoints and side effects
   - uploads, exports, generated PDFs, and soft deletes
   - DB tables, joins, lookup tables, and permission checks
5. Present a short analysis before large edits when scope is unclear. Include files to create/change and any behavior deliberately deferred.
6. Implement in dependency order:
   - TableMap, Entity, Factory if missing
   - Repository methods for all DB access
   - Form DTO/FormFactory for editable data
   - Controls and control factories
   - Presenter and Latte templates
   - control `.neon` files and `config/includes.neon`
   - routes, ACL resources, authorizer permissions if needed
7. Verify with PHPStan and a page-load check when feasible.

## Implementation Rules

- Keep DB access in repositories. Controls and presenters may use repositories, not `Nette\Database\Explorer` directly.
- Do not use legacy numeric `permission` filtering in repositories to decide what the current user may see or execute. Repositories should return domain/menu data based on domain filters only; authorization belongs at every UI/action boundary.
- Protected UI must be guarded with `$user->isAllowed($resource)` at every relevant layer, always:
  - Latte: render protected links, buttons, form sections, and menu items only inside an `isAllowed` condition.
  - Presenter/control/component factory: do not create protected controls/forms without checking `isAllowed`; return/throw 403 when unauthorized.
  - Form factory: inject `Nette\Security\User` when the factory builds a protected form; refuse to create that form unless `isAllowed` passes, and check again inside the factory's own `onSuccess` callback before invoking persistence callbacks.
  - Presenter/control form success or signal handler: check `isAllowed` immediately before mutating data, deleting rows, exporting files, revealing protected data, or editing access/status fields, even if the button/form was hidden in Latte and checked in the form factory.
- Every legacy `if ($user->permission ...)` in a controller or template is a protected behavior and must be explicitly mapped before implementation:
  - For page-level gates, use the page's existing `Resource`.
  - For subpage or partial UI gates, create a dedicated `Resource` in `app/Model/Enum/Acl/Resource.php`, allow the matching role in `AuthorizatorFactory`, guard the Latte block with `$user->isAllowed(...)`, and do not load data for that block in the presenter/control unless the resource is allowed.
  - Document the mapping in the audit note as `legacy permission -> new Resource -> allowed role`.
- Do not replace authorization with disabled controls. If the user is not allowed, do not render the protected control; if the user is allowed, render an active control and enforce the same permission again server-side.
- Preserve the legacy visual structure exactly unless the user explicitly asks for redesign. Reuse the old wrapper classes, spacing classes, inline dimensions, row structure, and box sizing from the legacy template/render helper. Do not replace legacy rows with Bootstrap list groups, badges, equal-height cards, or other new visual patterns by preference.
- Preserve legacy click targets exactly. If the old helper wrapped the full row in an `<a>`, the migrated Latte must keep the whole row clickable, not only the count, label, or badge.
- When rendering legacy color values inside inline CSS in Latte, use `|noescape`, for example `style="background:{$item['color']|noescape};"`. Without `|noescape`, Latte may escape `#` and break colors such as `#00BC2B`.
- Do not render legacy count rows with zero counts when the old helper skipped them. This applies to stats/status/country count widgets such as `renderCounts()` and `renderCountsCountry()`.
- Keep runtime control factory parameters simple, usually IDs. Pass page/filter state via setters on list controls.
- Register every control factory in `config/control/*.neon` and include it in `config/includes.neon`.
- Use `BaseFormFactory` for Nette forms and typed DTOs under `app/Model/Form/DTO/Admin/...`.
- Add `{varType ...}` declarations for Latte template variables.
- Every admin presenter template must set `{block pageTitle}...{/block}` so the layout `<h1>` is not empty.
- Use `StorageDirProvider` and configured parameters for file paths. Do not hardcode upload/export paths in new code.
- Convert old `$_GET` filters to typed presenter action parameters.
- Preserve legacy kebab-case URL parameter names exactly when they exist. In particular, the alphabet filter must use `first-letter` in generated URLs, not camelCase variants such as `firstLetter`.
- Convert old inline AJAX helpers to Nette forms or `handle*()` signals.
- Preserve legacy inline-edit behavior for fields that used classes like `updateSelect`, `updateDate`, `updateInput`, and `updateCheckbox`: no visible save button, submit on select/date/checkbox change and input/textarea change/blur, no full-page reload unless the legacy helper explicitly required one, and show save feedback by coloring the edited field or form border. Use the shared `www/js/autosaveForm.js` pattern with explicit `js-autosave-form` and `js-autosave-control` classes. Never migrate the unsafe legacy `table`/`column` client parameters; submit a concrete Nette form with CSRF and handle allowed fields in that form's server-side `onSuccess`.
- For repeated per-row forms, use `Nette\Application\UI\Multiplier`. In Latte, address multiplier children with a quoted component name such as `{form "registryForm-$row[id]"}` or `{control "registryForm-$row[id]"}`; do not write `{form registryForm-$row['id']}`, because Latte parses it as subtraction.
- For Nette Database list pagination, prefer `Selection::page($page, $itemsPerPage, $pageCount)` over manual `limit()`/offset math when the data already comes from a `Selection`.
- Convert old soft-delete AJAX endpoints to repository update methods plus `handleDelete()` or domain-specific signal names.
- Preserve legacy behavior unless the user explicitly asks for cleanup or redesign.
- After creating or modifying migration files, run `git add` for every file touched by the task before the final response unless the user explicitly says not to.

## Verification

Run the strongest local check that is practical:

```powershell
C:\wamp64\bin\php\php8.5.0\php.exe vendor/bin/phpstan analyse app --level 8 --memory-limit=512M
```

If cache problems occur, clear `temp/cache` using a safe PowerShell delete inside the project. For browser verification, use `http://opatrovatelky.local/...` routes when the local WAMP environment is available.

Always perform a real page-load check for migrated pages when the local host is available, not only `php -l` or PHPStan. Check the HTTP status and scan the response for Tracy error panels such as `tracy-section--error`. For admin pages during local migration, `AdminPresenter` may have login redirect disabled; if layout controls still assume a logged-in user, fix the development fallback or report the blocker before calling the page verified.

## Required Final Migration Audit

Before final delivery of any migrated page, run a deliberate legacy-vs-new audit. Do not skip this because the page loads or PHPStan passes. The goal is to catch small migration mismatches before the user sees them.

Create a short temporary audit note for yourself when the page has more than a trivial link mapping. Use `temp/migration-audit-{legacy-page}.md` or an equivalent scratch note outside the final product surface. It should compare the old template/controller/helper calls to the new presenter/repository/template. The file does not need to be committed unless the user asks, but the checks must be performed.

Audit checklist:

- Authorization:
  - Search the legacy controller, templates, subtemplates, and helpers for `permission` and write down every match.
  - Every protected link, button, form section, menu item, and row action in Latte must be guarded by `$user->isAllowed(...)` or an explicitly equivalent condition.
  - Every presenter action, `handle*()` signal, component/form factory for protected controls, and form success handler must check permission server-side immediately before creating protected UI or mutating/revealing data.
  - If legacy used numeric `permission` comparisons, translate the intent to the new role/ACL model and document the chosen rule in the audit note.
- Ordering and filtering:
  - For every list, write down the exact legacy data call and order, such as `getTable($table)` defaulting to `ORDER BY id DESC`, `getTableActive($table, 'DESC')`, or a custom `order(...)`.
  - The new repository must use the same ordering and filtering unless the user explicitly approved a change. Do not invent `ASC` because it seems natural.
- Visual parity:
  - Compare every rendered helper used by the old template, especially helpers like `acronym()`, `renderActiveStatus()`, `image()`, `tableHeader()`, `renderBtnAdd()`, and inline styles.
  - Reproduce helper output faithfully: classes, inline colors, dimensions, border radius, text visibility, clickable target size, and whether zero/empty values render.
  - If an old column rendered only an icon/dot/badge, the new column must not substitute text or a different Bootstrap component.
- Completeness:
  - Build a one-to-one list of legacy visible elements: page title, add buttons, table columns, filters, row actions, form fields, upload controls, status indicators, scripts, and subpage links.
  - Confirm each item exists in the new page or is explicitly marked deferred with a reason.
  - Check legacy subpages and update pages, not only the main page.
- Behavior:
  - Verify autosave fields keep legacy submit triggers and feedback behavior through the shared `autosaveForm.js` pattern.
  - Verify create/update/upload/delete actions use concrete Nette forms/signals and do not accept legacy generic `table` or `column` client parameters.
- Final verification:
  - Run PHP syntax/PHPStan as practical.
  - Load every migrated main and update page via HTTP and scan for Tracy error panels.
  - After making fixes found by the audit, re-run the relevant page-load check.

In the final response, mention the audit result briefly: authorization checked, ordering checked, visual/helper parity checked, completeness checked, and any known gaps. Do not present migrated work as complete if this audit has not been done.
