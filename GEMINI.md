# Opatrovatelky CRM - Project Instructions

## Architecture & Tech Stack
- **Framework:** Nette 3.2+
- **PHP Version:** 8.5
- **Latte:** 3.1+ (using Latte 3 tags)
- **Database:** Nette Database Explorer (mapping via `TableMap` classes)

## Project Structure & Conventions
- **Modules:**
  - `Login`: Authentication.
  - `Admin`: Main CRM functionality.
- **Model:**
  - `app/Model/Table`: Column mapping.
  - `app/Model/Entity`: Business entities.
  - `app/Model/Factory`: Entity creation.
- **UI:**
  - `app/UI/Admin/Control`: Reusable components (neon configuration used).
  - `app/UI/Admin/Form`: Shared forms.

- **Utils:**
  - `app/Model/Utils`: Object-oriented wrappers for common tasks (Array, Date, String, Validator, etc.).
  - Prefer using these services over raw PHP functions or static `Nette\Utils` calls for better testability and consistency.

## Documentation
- Detailed overview: [.claude/memory/project_overview.md](.claude/memory/project_overview.md)
- Migration map: [.claude/memory/reference_old_web_map.md](.claude/memory/reference_old_web_map.md)
- UI Controls: [docs/conventions/ui-controls.md](docs/conventions/ui-controls.md)

## Security (priority)
Full rules: `CLAUDE.md` → "Security". Same weight as PHPStan level 8, handled in the same change.
- Everything from the browser is untrusted (GET/POST, signal parameters, hidden fields, file names); a logged-in user can edit it in the console or type the URL by hand.
- Check permission on every entry point: `action*`, `handle*`, `createComponent*`, form `onSuccess`, AJAX/autosave. Controls check `isAllowed()` themselves.
- Never load a record by a bare ID from the request; load it through its owner and answer 404 on mismatch.
- Writes go through an explicit whitelist of fields; role, permission and `user_id` are never read from the request.
- File paths come from `StorageDirProvider` + the name stored in the DB, never from the request.

## Architecture (SOLID, DRY)
Full rules: `CLAUDE.md` → "Architecture: SOLID, DRY, Refactoring".
- Follow SOLID and DRY.
- Before adding a service or method, find the existing one and reuse or extend it.
- Duplicated logic is extracted into a shared service, trait or base class in the same change.
- Overcomplicated services are split and rebuilt on a fitting design pattern.
- A refactoring must not change behaviour or weaken permission checks.

## Coding Standards
- Follow Nette coding standards.
- Use `php-fixer@nette` for formatting.
- Entities should be type-safe and use PHP 8.5 features where appropriate.
