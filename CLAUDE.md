# Opatrovatelky CRM — Project Instructions for Claude

## Tech Stack
- **Framework:** Nette 3.2+
- **PHP:** 8.5
- **Templating:** Latte 3.1+ (Latte 3 syntax)
- **Database:** Nette Database Explorer + TableMap column mapping
- **Code Formatting:** php-fixer@nette

## Project Structure
```
app/
  Model/
    Table/       — TableMap classes for DB column mapping
    Entity/      — entity classes (populated by BaseFactory with constructor args)
    Factory/     — entity factory classes (BaseFactory pattern)
    DTO/         — data transfer objects
    Service/     — business logic services
    Repository/  — database repository classes
    Enum/        — enums (roles, ACL resources)
    Security/    — auth: AdminAuthenticator, Authorizator, ACL
  UI/
    Admin/
      Control/   — reusable components (thematic nesting: Control/Theme/ControlName/)
      Form/      — shared form classes
      Agency/    — Agency presenter + templates
      Babysitter/— Babysitter presenter + templates
      etc.
    Login/       — authentication module
  Model/
    Utils/       — OO wrappers for Nette\Utils and PHP functions (ArrayService, DateService, etc.)
config/
  development/   — database.neon, setup.neon (localhost:3307)
  production/    — production-specific config
  control/       — neon files for ControlFactory registration (manual, not auto-discovered)
  parameter.neon — StorageDirProvider paths
  services.neon  — DI container services
```

## Database & Environment

**Development:**
- URL: http://opatrovatelky.local
- Database: opatrovatelky_nette
- Host: localhost
- Port: 3307
- User: root
- Password: (empty)

**Environment files (`config/.env*`):** loaded by `App\Model\Settings\EnvironmentFileLoader`, called from `App\Bootstrap::loadEnvironment()`.
- Priority, highest first: real environment variable → `config/.env` (this machine, git-ignored, required for development and production) → `config/.env.<environment>` (shared non-secret defaults, committed, optional).
- Tests (`APP_ENV=test`) read only `config/.env.test` (committed, no real secrets) plus optional git-ignored `config/.env.test.local`. `config/.env` is never read in tests.
- New key: add it to `config/.env.example`, to `config/.env` on every machine, and to `config/.env.test` if tests need it. A key that may be missing on a machine goes to `Bootstrap::OptionalEnvironmentKeys`.
- Never put test-only values or secrets into PHP constants.

**Key Tables:**
- `sany_users` — users (id, name, second_name, acronym, email, password, permission, color, active, image)
- `sany_pages` — menu structure
- (other tables defined in app/Model/Table/*TableMap.php)

**Authentication & Authorization:**
- User roles: ADMIN, CEO, DEALER, DEALER_JUNIOR (enum: `App\Model\Enum\UserRole\UserRole`)
- ACL resources: `App\Model\Enum\Acl\Resource`
- Authenticator: `App\Model\Security\AdminAuthenticator`
- Authorizator: `App\Model\Security\Authorizator\AuthorizatorFactory`
- Role permissions are set only in `AuthorizatorFactory::ROLE_RESOURCES`; the table `sany_pages` drives the menu (name, order, active) and grants nothing, its `url` → resource/presenter mapping is the enum `App\UI\Admin\Control\Layout\Menu\MenuPage`.

## Control & Component Conventions

**Structure:** Controls live in `app/UI/{Module}/Control/{Theme}/{ControlName}/` with 4 files:
1. Control class
2. ControlFactory interface
3. PresenterTrait (used by presenter)
4. Latte template

**Rules:**
- Use **factory pattern** for all controls. Setter pattern **only** for pagination/filter controls.
- Controls work with **ActiveRow directly**, not Entity — PHP 8.5 property hook visibility causes fatal errors in Latte templates when Entity properties are private.
- Factory params: pass simple scalars (`userId: int`), never entity objects.
- **No hardcoded paths** — use `StorageDirProvider` from `config/parameter.neon`.
- Register ControlFactory interfaces manually in `config/control/*.neon` (not auto-discovered).

**Example structure:**
```
Control/Partner/PartnerList/
  PartnerListControl.php      — control class
  PartnerListControlFactory.php — interface
  PartnerListControlTrait.php  — trait for presenter
  PartnerListControl.latte     — template
```

## Security (priority — same weight as PHPStan level 8)

Every change to a file, service, method, presenter, control or form is checked for abuse by a **logged-in user**. Handle it in the same change, never as a later task. A task is not done while a hole is open, the same as with PHPStan errors.

**Principle:** everything that comes from the browser is untrusted — GET/POST parameters, signal parameters, hidden fields, persistent parameters, file names, headers. The user can edit any of it in the console or type the URL by hand. Hiding a button in Latte is not protection; the server decides.

**Rules:**
- **Permission on every entry point:** `action*`, `handle*` (signals), `createComponent*`, form `onSuccess`, AJAX/autosave endpoints.
  - Presenter: `getResource()` / `getPrivilege()`, checked in `AdminPresenter::startup()`.
  - Control: its own `$this->user->isAllowed(Resource::X->value)` in every `handle*`, `createComponent*` and `onSuccess` — a control can be attached to another presenter. Pattern: `app/UI/Admin/Control/Babysitter/BabysitterDocuments/BabysitterDocumentsControl.php`.
- **Record ownership (IDOR):** never load a record by a bare ID from the URL/POST. Load it through its parent/owner (`FileRepository::findDocument($dir, $ownerId, $id)`) and answer 404 when it does not match. Write to the record verified on the server, never to the ID named by a hidden field.
- **Writes go through a whitelist:** name the columns, autosave contexts and fields explicitly (`AdminPresenter::getAllowedAutosaveContexts()`, `AutosaveFieldUpdateService`). No `update($request->getPost())`, no column/table/order name taken from the request.
- **Role, permission, `user_id`, owner** are never read from the request — only from `$this->user` and `AuthorizatorFactory::ROLE_RESOURCES`. Changing another user's role or access needs its own permission check.
- **State changes:** keep Nette's built-in same-site protection of signals (never add `#[CrossOrigin]`), create forms through `BaseFormFactory` (`addProtection()`), custom AJAX endpoints require POST + `isFrom([FetchSite::SameSite, FetchSite::SameOrigin])`.
- **Files:** build paths from `StorageDirProvider` + the name stored in the DB, never from the request (path traversal). Uploads: check extension, size and content type, generate the file name. Private files are served only by an authorized handler.
- **Output:** no `|noescape` on user data; SQL only through Explorer with parameters.
- **Sensitive changes** are logged through `ChangeAuditLogger`.
- **Tests:** a new handler/endpoint gets a test for a foreign ID, a missing permission and a guest. Patterns: `tests/Smoke/GuestAccessSmokeTest.php`, `tests/Smoke/OffcanvasAccessSmokeTest.php`, `tests/Api/UserAccessAutosaveContractTest.php`.

**Check before handing over** — for every new or changed entry point answer:
1. Who can call it, and is that checked on the server?
2. What happens when the ID is changed to someone else's record?
3. What happens when an extra field or another context is sent?
4. What happens when it is called directly by URL, without the UI?

An existing hole found outside the scope of the task is reported to the user with the file and line; it is not silently rewritten at scale.

**Audit of existing code:** `/security-fix <folder|file|Class::method|URL|module|topic|next|status>` (skill in `.claude/skills/security-fix/`). It inventories the entry points, attacks each one, fixes with a proving test and keeps the ledger in `workflow/_active/security-audit/`.

## Architecture: SOLID, DRY, Refactoring

- **Search before writing:** before adding a service or method, look for an existing one (`app/Model/Service`, `app/Model/Utils`, `app/Model/Repository`) and reuse or extend it. Never create a second service for the same job.
- **DRY:** the same logic in a second place is extracted into a shared service, trait or base class in the same change. Known candidate: the `*DocumentsControl` classes (Babysitter, Family, Partner, Agency, Turnus) carry near-identical code.
- **SOLID:**
  - **S** — one class, one responsibility. Presenters and controls only orchestrate; logic belongs to a service, queries to a repository.
  - **O** — new variants come as new classes or enum cases with `match`/strategy, not as a growing `if/else` in an existing method.
  - **L** — a subclass does not change the contract of its parent.
  - **I** — small purpose-built interfaces, not one large one.
  - **D** — dependencies through the constructor/DI; no `new` of services and no static calls inside logic.
- **Overcomplicated services** (long methods, many dependencies, branching by type) are refactored: split them and use a fitting pattern (Strategy, Factory, DTO/Value Object, Template Method).
- **Scope:** code the task touches is refactored right away. A larger refactoring outside the task is reported with a proposal and done only on request.
- A refactoring must not change behaviour or weaken permission checks; run PHPStan level 8 and the tests after it.

## Model Utilities

Utilities in `app/Model/Utils` provide an object-oriented interface for common tasks, often wrapping `Nette\Utils` or PHP internal functions. Use these services via DI for better testability.

- **ArrayService:** Paginating, searching, sorting, and merging arrays.
- **Date:** `DateService`, `MonthService`, `QuarterService`, `YearService` for date manipulation.
- **StringService:** Wraps `Nette\Utils\Strings` with additional helpers (removing diacritics, webalizing, etc.).
- **Validator:** `EmailValidator`, `ImageValidator`, `UrlValidator`.
- **File/Dir/Path:** Filesystem operations.
- **JsonService:** Wrapper for `Nette\Utils\Json`.
- **NumberService:** Formatting and numeric operations.
- **UrlVersionGenerator:** Appends versions to URLs for cache busting.
- **Paginator:** Logic for data pagination.
- **TypeConverter/TypeChecker:** Safe type casting and checking.

## Entity Property Conventions

**Use plain public properties with constructor promotion, NOT private with hooks:**

✅ **Do this:**
```php
public function __construct(
    public readonly int $id,
    public ?string $name,
    public ?int $permission,
) {}
```

❌ **Never do this:**
```php
private string $name;
public function __get($name) { ... }  // Latte can't read private props
```

**Why:** PHP 8.5 property hook visibility is inherited from property visibility. Private property + private get hook = fatal error when Latte tries to read `{$item->name}` from a template.

**When to use property hooks:** Only when there's real logic (validation, transformation, computed value). For immutability, use `public readonly` or `public private(set)`.

## Code Standards

- Follow Nette coding standards and PSR-12.
- Use `php-fixer@nette` for formatting.
- Type hint all parameters and return types.
- Use PHP 8.5 features (constructor promotion, match expressions, nullsafe operator).
- The Security and SOLID/DRY rules above are mandatory and checked on every change together with PHPStan level 8.

## PHPStan & Type Checking

**Critical:** Always run PHPStan with PHP 8.5, not system PHP 8.2.

```bash
/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/phpstan analyse --level 8 --memory-limit=512M
```

The system default PHP is 8.2.29. PHPStan will report incorrect errors with it.

## Migration Reference

The old CRM lives at `C:\wamp64\www\monika\opatrovatelky\old` (simple MVC with `?page=` routing).

**Key page → presenter mappings:**
- opatrovatelky → Admin:Babysitter
- families → Admin:Family
- partneri → Admin:Partner
- turnus → Admin:Turnus
- agencies → Admin:Agency
- todo → Admin:Todo
- user-management → Admin:UserManagement
- (full mapping in `.claude/memory/reference_old_web_map.md`)

## Modules

- **Login:** Authentication only
- **Admin:** Main CRM functionality (Babysitter, Family, Partner, Agency, Turnus, etc.)

## Important Paths

- `config/parameter.neon` → StorageDirProvider for path constants
- `app/Model/Security/AdminAuthenticator.php` → login logic
- `app/Model/Security/Authorizator/AuthorizatorFactory.php` → ACL/permissions
- `.claude/memory/` → detailed project documentation
- `GEMINI.md` → overview of these same instructions (shorter version)
