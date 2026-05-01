---
name: migrate-page
description: >
  Migrate an old CRM page to the new Nette 3.2 framework. Invoke when user wants to migrate,
  convert, port, or rebuild an old page. Trigger on: "migrate", "port", "convert", "prenes",
  "prenesieme", "prerabka", or any old page name (families, opatrovatelky, partneri, turnus, etc.).
---

## Overview

Old CRM: `C:\wamp64\www\monika\opatrovatelky\old\`
New framework: `C:\wamp64\www\monika\opatrovatelky\local\` (Nette 3.2, PHP 8.5, Latte 3.1+)

**Naming rule**: Always use proper English names. Never use Slovak names in code:
- opatrovatelky -> Babysitter
- rodiny/families -> Family
- partneri -> Partner
- pracovnici -> Employee
- turnus -> Rotation (or Shift)
- opatrovanie -> Care
- krajiny/country -> Country
- dokumenty -> Document
- navrhy/proposal -> Proposal

Before starting, invoke the Nette skills: `nette-architecture`, `nette-forms`, `nette-latte` as needed.

---

## Phase 1: ANALYSIS

1. Read [old-web-map.md](references/old-web-map.md) to find controller/template paths for the requested page
2. Read the old files:
   - Controller: `old/mvc/controller/{page}.php`
   - Template: `old/template/pages/{page}.php`
   - Update controller (if exists): `old/mvc/controller/{page}-update.php`
   - Update template (if exists): `old/template/pages/{page}-update.php`
   - Sub-templates directory (if exists): `old/template/pages/{pageName}/`
   - Model class (if exists): `old/mvc/{domain}/{Class}.php`
3. Identify:
   - **DB tables**: `Table::$xxx` references -> map to existing TableMap classes in `app/Model/Table/`
   - **List view**: table columns, filters (selects, text inputs), sorting, pagination needs
   - **Detail view tabs**: sub-page includes, which data each tab shows
   - **Inline editing**: `inputAjax()`, `selectAjax()`, `textareaAjax()`, `checkboxAjax()` calls -> these become Nette form fields
   - **AJAX operations**: `renderBtn*()` calls, `$.ajax` endpoints -> become Nette signals (`handle*()`)
   - **Relations**: `renderJoinColumn()` calls indicate FK joins -> repository methods needed
   - **File uploads**: `uploadFile`/`uploadImage` usage -> FileService/ImageService integration
   - **Permissions**: `$user->permission >= N` -> map to `Resource` enum + `UserRole`
4. Check which TableMap, Entity, Factory, Repository classes already exist in the project
5. Present analysis summary to user. Ask what to include/exclude before proceeding.

---

## Phase 2: PLANNING

### Presenter
One presenter per page group. Location: `app/UI/Admin/{Domain}/{Domain}Presenter.php`
- List page -> `actionDefault(int $page = 1, ...filters)`
- Detail/edit page -> `actionDetail(int $id)`
- Templates in `app/UI/Admin/{Domain}/templates/{Domain}.default.latte`, `{Domain}.detail.latte`

### Controls (decision tree)
Each control = 4 files in `app/UI/Admin/Control/{Domain}/{ControlName}/`:
- `{Name}Control.php`
- `{Name}ControlFactory.php` (interface)
- `{Name}PresenterTrait.php`
- `templates/{Name}Control.latte`

**When to create a control:**
- List view with table -> `{Domain}ListControl` (includes filters, pagination)
- Detail view (simple, no tabs) -> `{Domain}DetailControl`
- Detail view (with tabs) -> one control per tab: `{Domain}MainControl`, `{Domain}AddressControl`, etc.
- Reusable widget -> `app/UI/Admin/Control/{Domain}/Widget/{WidgetName}/`
- Form that appears in multiple places -> separate FormControl

**Factory pattern rules:**
- Factory `create()` params: only simple values (`int $id`, `int $page`), never entities
- Setter pattern ONLY for `page` and `filter` values on list controls
- All other dependencies via constructor (DI container)

### Forms
Location: `app/UI/Admin/Form/{Domain}/{FormName}/`
- `{FormName}FormFactory.php` - creates the form, uses `BaseFormFactory`
- `{FormName}FormDTO.php` - `final readonly class` with typed properties
- Old inline AJAX editing -> convert to a Nette form inside the detail control
- Old modal forms -> separate FormFactory

### Repository / TableMap / Entity / Factory
- Check existing files first! Only create if missing.
- Repository: `app/Model/Repository/{Domain}Repository.php` extends `BaseRepository`
- TableMap: `app/Model/Table/{Domain}TableMap.php` extends `BaseTableMap`
- Entity: `app/Model/Entity/{Domain}Entity.php` extends `BaseEntity`
- Factory: `app/Model/Factory/{Domain}Factory.php` extends `BaseFactory`

### Service (if needed)
- Location: `app/Model/Service/{Domain}/{ServiceName}Service.php`
- Only create when business logic is too complex for repository alone

### Config registration
- Each ControlFactory -> `config/control/{controlName}.neon` with `implement:` directive
- Add include line to `config/includes.neon`
- Repository/Factory/TableMap are AUTO-DISCOVERED (no manual .neon needed)
- FormFactory is AUTO-DISCOVERED (matches `*Factory` pattern)

### ACL
- Add case to `app/Model/Enum/Acl/Resource.php` if new resource needed
- Update `app/Model/Security/Authorizator/AuthorizatorFactory.php` with role permissions

### Router
- Add route to `app/Router/RouterFactory.php` under Admin module

### Paginator
- If list has >20 items typically, integrate `PaginatorFactory`
- Use shared paginator template (create at `app/UI/Admin/templates/components/paginator.latte` if not exists)

Present file list to user. Confirm before creating.

---

## Phase 3: IMPLEMENTATION

1. Read [patterns.md](references/patterns.md) for exact code templates
2. Create files in dependency order:
   1. TableMap (if new)
   2. Entity (if new)
   3. Factory (if new)
   4. Repository (if new)
   5. Service (if needed)
   6. Control files (all 4 per control)
   7. Form files (FormFactory + DTO)
   8. Presenter
   9. Latte templates (presenter + control templates)
   10. Config .neon files for controls
   11. Update `config/includes.neon`
   12. Update `Resource` enum + `AuthorizatorFactory` (if new resource)
   13. Update `RouterFactory` (add route)
   14. Paginator latte template (if not exists yet)

### Key implementation rules
- **Latte**: Every template MUST have `{varType}` declarations for ALL template variables
- **Controls**: Use ActiveRow, NEVER Explorer directly. Explorer only in Repository.
- **StorageDirProvider**: Never hardcode paths. If new paths needed, add to `StorageDirProvider` + `config/parameter.neon`
- **Namespaces**: Follow exact project namespace pattern `App\UI\Admin\Control\{Theme}\{Name}`
- **PHP 8.5**: Use property hooks in entities (`private ?string $field { get; set; }`)
- **Forms**: Use `BaseFormFactory::create()` as base, add protection + novalidate
- **Inline edit migration**: Old `inputAjax(label, table, column, value, id)` -> Nette text input in form. Old `selectAjax(...)` -> Nette select. Group related fields into logical forms.
- **Filter migration**: Old `$_GET['country']` -> presenter action param `?int $country = null`, pass to control via setter
- **Tab migration**: Old `?page=xxx-update&id=1&address=1` -> either separate action or control per tab

---

## Phase 4: VERIFICATION

1. Run PHPStan: `C:\wamp64\bin\php\php8.5.0\php.exe vendor/bin/phpstan analyse app --level 8 --memory-limit=512M`
2. Clear cache: `rm -rf temp/cache`
3. Check page loads at `http://opatrovatelky.local/{route}` (use WebFetch or suggest manual check)
4. Verify all `{control}` tags render without errors
5. Fix any PHPStan errors before finishing
