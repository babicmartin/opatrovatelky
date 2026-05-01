# Project Conventions

## Stack

- Framework: Nette 3.2+
- PHP: 8.5
- Templates: Latte 3.1+
- Main modules: `Login` and `Admin`
- Auth table: `sany_users`
- Roles: `App\Model\Enum\UserRole\UserRole`
- ACL resource enum: `App\Model\Enum\Acl\Resource`
- Authorizer: `App\Model\Security\Authorizator\AuthorizatorFactory`

## Layout

| Concern | Location |
| --- | --- |
| Admin presenters | `app/UI/Admin/{Domain}/{Domain}Presenter.php` |
| Admin presenter templates | `app/UI/Admin/{Domain}/templates/{Domain}.default.latte` |
| Admin controls | `app/UI/Admin/Control/{Domain}/{ControlName}/` |
| Control template | `app/UI/Admin/Control/{Domain}/{ControlName}/templates/{ControlName}Control.latte` |
| Admin forms | `app/UI/Admin/Form/{Domain}/{FormName}/` |
| Form DTOs | `app/Model/Form/DTO/Admin/{Domain}/{FormName}/` |
| Repositories | `app/Model/Repository/{Domain}Repository.php` |
| Table maps | `app/Model/Table/{Domain}TableMap.php` |
| Entities | `app/Model/Entity/{Domain}Entity.php` |
| Entity factories | `app/Model/Factory/{Domain}Factory.php` |
| Control DI config | `config/control/{controlName}.neon` |
| Config includes | `config/includes.neon` |
| Routes | `app/Router/RouterFactory.php` |

## Existing Patterns

Control folders normally contain:

- `{Name}Control.php`
- `{Name}ControlFactory.php`
- `{Name}PresenterTrait.php`
- `templates/{Name}Control.latte`

Register control factories with:

```neon
services:
    -
        implement: App\UI\Admin\Control\{Domain}\{Name}\{Name}ControlFactory
```

Entity classes use PHP 8.5 property hooks. Existing generated entities often type DB-backed scalar properties as nullable strings even for numeric columns. Match surrounding domain style unless a stronger type is already established.

Table maps extend `BaseTableMap` and define:

```php
public const string TABLE_NAME = 'sn_example';
public const string TABLE_PREFIX = 'example_mapper';
public const string COL_ID = 'id';
```

Factories extend `BaseFactory` and return the entity class and table map class.

Repositories extend `BaseRepository`. `findAll()`, `insert()`, `update()`, and `getItem()` are protected helpers. Expose explicit public methods needed by controls/forms, such as `findById()`, `findFiltered()`, `softDelete()`, or `updateFromDto()`.

## UI Rules

- Presenter templates usually render controls with `{control name}`.
- Controls prepare template variables in `render()`.
- Latte templates must declare template variables with `{varType ...}`.
- Use existing Admin layout, Bootstrap classes, and current controls as the baseline.
- Do not add marketing-style screens. CRM pages should be dense, practical, and easy to scan.

## Auth And Permissions

New Admin presenters should implement the app's resource pattern:

```php
protected function getResource(): string
{
    return Resource::{DOMAIN}->value;
}
```

When adding a new resource, update both `Resource` and `AuthorizatorFactory`. Map old numeric permission checks to the existing `UserRole` model; do not copy old permission logic directly into templates.

## Environment

Development host: `opatrovatelky.local`

Database is configured under `config/development` and `config/production`. Do not hardcode connection settings.

Use this PHP binary for local verification when available:

```powershell
C:\wamp64\bin\php\php8.5.0\php.exe
```
