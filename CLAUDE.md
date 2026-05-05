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

**Key Tables:**
- `sany_users` — users (id, name, second_name, acronym, email, password, permission, color, active, image)
- `sany_pages` — menu structure
- (other tables defined in app/Model/Table/*TableMap.php)

**Authentication & Authorization:**
- User roles: ADMIN, CEO, DEALER, DEALER_JUNIOR (enum: `App\Model\Enum\UserRole\UserRole`)
- ACL resources: `App\Model\Enum\Acl\Resource`
- Authenticator: `App\Model\Security\AdminAuthenticator`
- Authorizator: `App\Model\Security\Authorizator\AuthorizatorFactory`

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
