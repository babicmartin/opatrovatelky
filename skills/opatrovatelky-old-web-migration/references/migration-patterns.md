# Migration Patterns

## Legacy File Discovery

For a requested page `{page}`, check:

```text
old/mvc/controller/{page}.php
old/template/pages/{page}.php
old/mvc/controller/{page}-update.php
old/template/pages/{page}-update.php
old/template/pages/{domain}/
old/mvc/{domain}/{Class}.php
old/ajax/*.php
old/web/js/
```

Read subtemplates before designing detail controls. In the old app, a visible tab can hide important save logic or AJAX calls in included PHP files.

## Old Helpers To New Nette

| Old pattern | Meaning | New pattern |
| --- | --- | --- |
| `inputAjax(...)` | inline text update | Nette form field or update signal |
| `selectAjax(...)` | inline select update | Nette select with repository-backed options |
| `textareaAjax(...)` | inline textarea update | Nette textarea in tab form |
| `checkboxAjax(...)` | boolean/multi toggle | checkbox form or `handleToggle*()` |
| `renderBtn*()` | generated action button | Latte link to presenter/action/signal |
| `renderJoinColumn()` | FK lookup column | repository query with relation or explicit lookup repository |
| `$_GET[...]` | filter or tab state | typed presenter action parameter |
| `ajax/hide*.php` | soft delete | repository `softDelete()` plus signal |
| direct upload path | file/document storage | upload form plus `StorageDirProvider` |

### Legacy Render Helpers

`WebApp::$render->renderJoinColumn($id, $table, $column, $echo = true, $badge = false, $truncate = false)` loads one row from `$table` by `$id` and renders/returns `$column`.

- With `$echo = false`, it returns the looked-up column value. Example: country image lookup for a family state should become a repository join from `sn_families.state` to `sn_country.id`, selecting `sn_country.image`.
- With `$badge = true`, it renders `<span class="badge" style="background-color:{color}">{column}</span>`. Join the lookup/status table and select both the display column and `color`.
- With `$truncate = true`, it applies `substr(value, 0, 20)`. Prefer truncating in repository/view model for migrated list widgets.

Important lookup mappings seen on the homepage migration:

| Legacy expression | Join target |
| --- | --- |
| `renderJoinColumn($data->status, Table::$turnusStatus, 'status', true, true)` | `sn_turnus.status = sn_status_turnus.id`, render `sn_status_turnus.status` + `color` badge |
| `renderJoinColumn($data->invoice_status, Table::$faStatus, 'status', true, true)` | `sn_turnus.invoice_status = sn_status_fa.id`, render `sn_status_fa.status` + `color` badge |
| `renderJoinColumn($data->family_id, Table::$families, 'name')` | join `sn_families` by `sn_turnus.family_id` |
| `renderJoinColumn($data->babysitter_id, Table::$opatrovatelky, 'surname')` | join `sn_opatrovatelky` by `sn_turnus.babysitter_id` |
| `renderJoinColumn($data?->ref(Table::$families, 'family_id')?->state, Table::$country, 'image', false)` | join `sn_country` by `sn_families.state` and select `image` |

`WebApp::$functions->generateDateToWeb($date)` formats the first 10 chars of a SQL date from `Y-m-d` to `d.m.Y`; empty/invalid values return an empty value.

`WebApp::$render->image('country', $file, '16px', 'rectangle')` reads from `web/img/country/{file}` in legacy. The migrated public path is `www/img/country/{file}`, so use `{$basePath}/img/country/{$file}`.

Homepage turnus highlighting uses `WebApp::$turnus->isInvoiceUnpaid($row)`: highlight when babysitter is not in `[21, 22, 23, 107, 111, 358]`, invoice status is in `[0, 1, 2, 4, 6]`, turnus status is not in `[0, 30]`, and the joined family state is `3`.

## List Page Shape

Extract and implement:

- source table and aliases
- visible columns and joined labels
- filters from `$_GET`, form controls, or JavaScript
- default sort
- action links to detail/update pages
- create/delete buttons
- pagination or expected item count

Prefer a `{Domain}ListControl` with setters for page and filters. The presenter action owns typed URL parameters; the control owns rendering and calls repository methods.

## Detail Page Shape

For simple detail pages, create one detail control. For tabbed pages, create one control per cohesive tab only when the tab contains substantial logic. Small static tabs can remain in one detail template if that matches the current app better.

Old URLs like:

```text
?page=families-update&id=1&address=1
```

usually become either:

```text
/family/1/?tab=address
```

or separate controls rendered by a detail template. Match existing routing and UX before adding new route patterns.

## Repository Methods

Keep old SQL interpretation in repositories. Typical methods:

```php
public function findById(int $id): ?ActiveRow
public function findFiltered(...): Selection
public function softDelete(int $id): int
public function updateFromDto(int $id, SomeFormDTO $dto): int
```

Validate column names through `{Domain}TableMap::COL_*` constants. Add missing constants instead of using raw strings repeatedly.

## Forms

Use `BaseFormFactory` as the base. Keep form creation separate from persistence unless current project conventions already combine them. Convert old inline-edit fields into logical form groups by tab, not one form per old helper call.

DTOs should live under `app/Model/Form/DTO/Admin/...` and expose getters expected by repository/service code.

## Signals

Use signals for small row actions that are not full forms:

```php
public function handleDelete(int $id): void
{
    $this->repository->softDelete($id);

    if ($this->getPresenter()->isAjax()) {
        $this->redrawControl();
        return;
    }

    $this->getPresenter()->redirect('this');
}
```

Use Naja attributes in Latte only if the project already loads Naja for the area being migrated.

## Files, Images, PDF

Old assets live under legacy `web/assets`, `web/js`, `web/img`, `web/documents`, and `web/export`.

When migrating uploads or exports:

- find existing storage providers/services first
- keep paths in config and `StorageDirProvider`
- keep document metadata in repositories
- avoid copying old path constants into controls/templates

## Verification Checklist

- `php -l` on newly created PHP files if PHPStan is too broad.
- PHPStan level 8 on `app` when feasible.
- New control factory `.neon` included in `config/includes.neon`.
- New presenter route added and reachable.
- New Latte templates contain `{varType}` declarations for all assigned variables.
- No direct `Explorer` use outside repositories unless matching a pre-existing local pattern.
