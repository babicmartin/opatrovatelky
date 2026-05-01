---
name: Project Overview - Opatrovatelky CRM
description: Nette Framework CRM project structure, modules, database, and authentication setup
type: project
---

CRM application rebuilt from old web. Requires login before access to Admin.

- **Framework**: Nette 3.2+, PHP 8.5, Latte 3.1+
- **Modules**: Login (authentication) and Admin (main CRM)
- **DB table for users**: `sany_users` (columns: id, name, second_name, acronym, email, password, permission, color, active, image)
- **Roles**: ADMIN, CEO, DEALER, DEALER_JUNIOR (in App\Model\Enum\UserRole\UserRole)
- **ACL**: App\Model\Enum\Acl\Resource, App\Model\Security\Authorizator\AuthorizatorFactory
- **Auth**: App\Model\Security\AdminAuthenticator
- **Structure**:
  - app/UI/Admin/Control - shared controls
  - app/UI/Admin/Form - shared forms
  - app/Model/Table - database column mapping (TableMap classes)
  - app/Model/Entity - entity classes
  - app/Model/Factory - entity factories
  - config/development/ and config/production/ - environment-specific configs (database.neon, setup.neon)

**Why:** Understanding the project layout speeds up future work.
**How to apply:** Use these paths and conventions when adding features or fixing issues.
