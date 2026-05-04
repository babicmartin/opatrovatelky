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

## Documentation
- Detailed overview: [.claude/memory/project_overview.md](.claude/memory/project_overview.md)
- Migration map: [.claude/memory/reference_old_web_map.md](.claude/memory/reference_old_web_map.md)
- UI Controls: [docs/conventions/ui-controls.md](docs/conventions/ui-controls.md)

## Coding Standards
- Follow Nette coding standards.
- Use `php-fixer@nette` for formatting.
- Entities should be type-safe and use PHP 8.5 features where appropriate.
