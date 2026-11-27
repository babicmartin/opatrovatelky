# Project map for an audit

How to get from a target to the code that answers a request, and where the project keeps its security mechanisms. Method names are given instead of line numbers because other sessions edit these files; find the line with Grep.

## Contents

1. From a URL to code
2. Components that exist on every page
3. How a presenter is protected
4. Autosave: one signal that writes to many tables
5. Where things are
6. Working with the test suite

## 1. From a URL to code

Routes are in `app/Router/RouterFactory.php`. Every admin route has the mask `<presenter>[/<action>[/<id>]]`; kebab-case presenters (`partner-family`, `missing-registry`, `change-log`, `login-log`, `user-management`) are listed by hand, the rest fall into the generic mask. There is no `/admin` prefix. Login is `/login/` and `/logout/`.

If the `nette-inspector` MCP server is connected, `mcp__nette-inspector__router_match_url` resolves a URL exactly. Otherwise read it off the mask:

| Part of the URL | Meaning | Where the code is |
|---|---|---|
| `/babysitter/update/5` | presenter `Admin:Babysitter`, action `update`, `id = 5` | `app/UI/Admin/Babysitter/BabysitterPresenter.php`, `actionUpdate(int $id)` and `renderUpdate()` |
| template | | `app/UI/Admin/Babysitter/templates/Babysitter.update.latte` |
| `?do=babysitterDocuments-delete` | component `babysitterDocuments`, signal `delete` | `createComponentBabysitterDocuments()` in the presenter or in a `*PresenterTrait` it uses, then `handleDelete()` of the control it returns |
| `&babysitterDocuments-id=12` | argument `$id` of that signal | signal arguments are prefixed with the component name |
| `?do=delete` | signal of the presenter itself | `handleDelete()` in the presenter |
| `?do=autosavePartial` | the shared autosave signal | `AdminPresenter::handleAutosavePartial()`, see section 4 |
| `?do=documentForm-12-submit` under a component | a form inside a `Multiplier`; `12` is the multiplier key | the closure in `createComponentDocumentForm()` |

A page consists of more than its presenter. For a URL target, the scope is:

1. the presenter class and every trait it uses,
2. the action and render method of the URL, plus every `handle*` of the presenter (any signal can be sent to any action of the same presenter),
3. every `createComponent*` the presenter has, whether or not the template of this action renders it. A component is created on demand when a request names it, so a form that the page does not show can still be submitted to it,
4. the controls those factories return, with their own `handle*`, `createComponent*` and templates,
5. the form factories they use (`app/UI/Admin/Form/<Module>/`),
6. the services and repository methods reached from there,
7. the components of section 2.

`{control name}` and `{form name}` lines in the inventory of a `.latte` file tell which components the page really renders; `signal-link` lines show the signals a user is offered. Both are a subset of what can be called.

Controls live in `app/UI/Admin/Control/<Theme>/<Name>/` as `<Name>Control.php`, `<Name>ControlFactory.php`, `templates/<Name>Control.latte` and usually a `<Name>PresenterTrait.php`. Some controls have no trait and are created by a `createComponent*` written in the presenter. Either way, grep `<Name>ControlFactory` to find every place a control is attached. The factory is registered in `config/control/*.neon`.

### Order in which a presenter runs

`startup()` → `action<Action>()` → components named in the request are created → the signal is handled (`handle*`, or a form's `onSuccess`) → `beforeRender()` → `render<Action>()`.

Two things follow. A signal runs after the action of the URL it was sent to, so state the handler needs must have been set by **that** action: a control whose owner ID is filled in `actionUpdate()` has ID 0 when the signal is sent to `/turnus/` or `/turnus/month/3` instead (the inventory flags such properties as `context-default-zero`). And a signal runs before anything is rendered, so nothing in a template or in `render*()` can protect it.

### What Nette already refuses

Installed: nette/application 3.3, nette/forms 3.3, nette/http 3.4, Latte 3.1 (`composer.lock` has the exact versions).

- Every `handle*` signal and every submit of a `Nette\Application\UI\Form` requires a same-origin request (`Sec-Fetch-Site`, with the strict cookie as fallback). A request from another site is not answered with 403: `detectedCsrf()` redirects to the same page without running the signal. Only `#[CrossOrigin]` / `#[Requires(sameOrigin: false)]` or `allowCrossOrigin()` on a form switch this off.
- Same origin is not permission. A link or a script on the application's own pages, a pasted URL and the employee's own console all pass this check.
- `addProtection()` is deprecated in this version because the default covers it. `BaseFormFactory` is the project convention for creating forms; a form created another way breaks the convention but is not unprotected.
- An in-process test has to look same-origin or the signal is silently skipped: the reference tests send the cookie `Helpers::StrictCookieName`.

The enum `App\UI\Admin\Control\Layout\Menu\MenuPage` maps a menu row to its resource and presenter. It decides what the menu shows and grants nothing.

## 2. Components that exist on every page

`AdminPresenter` uses these traits, so every admin presenter has these components and their signals can be sent to any admin URL by any signed-in user, whatever resource that page needs:

| Component | Control |
|---|---|
| `menu` | `Control/Layout/Menu/MenuControl` |
| `search` | `Control/Layout/Search/SearchControl` (has signals, returns JSON) |
| `toolbar` | `Control/Layout/Toolbar/ToolbarControl` |
| `familyOffcanvas`, `partnerOffcanvas`, `projectOffcanvas` | `Control/Layout/Toolbar/<Name>Offcanvas/` |
| `userProfileImage` | `Control/User/UserProfileImage/UserProfileImageControl` |
| `logoutForm` | created in `AdminPresenter`, handled by `LoginPresenter` |

A `dealerJunior` on the Home page can address `?do=search-…` or the partner panel directly. These controls must each decide by their own `isAllowed` what the current role may receive, because the presenter's resource says nothing about them.

## 3. How a presenter is protected

`AdminPresenter::startup()` does two things: redirects a guest to `@login`, and if `getResource()` returns a resource, refuses with 403 unless `isAllowed($resource, $this->getPrivilege())`. `getPrivilege()` returns `'default'` everywhere and the ACL grants whole resources, so privileges do not distinguish read from write: whoever may open a module may call every action and signal of its presenter unless the code adds a second check.

Consequences to check on each presenter:

- `getResource()` returning `null` means "any signed-in user". `UserManagementPresenter` does this on purpose (everyone may open the own profile) and checks inside each action and in its own `validateAutosavePartialRequest()`. Every entry point of such a presenter needs its own check.
- A sub-feature with its own resource (`FAMILY_MANAGEMENT`, `WORKER_MANAGEMENT`, `TODO_VIEW_ALL`, `PARTNER_FAMILY`) is not covered by the presenter's resource. Look for the explicit `isAllowed` at the entry point, not only in the template.
- Error presenters and `Login:Login` do not extend `AdminPresenter`.

ACL: `App\Model\Security\Authorizator\AuthorizatorFactory`. `ROLE_RESOURCES` lists, for each role, the resources that start at that role; roles inherit `dealerJunior` ← `dealer` ← `ceo` ← `admin`, `admin` is allowed everything, `guest` nothing. Resources are the enum `App\Model\Enum\Acl\Resource`, roles `App\Model\Enum\UserRole\UserRole`.

Controls check for themselves. The pattern is an `isAllowed(Resource::X->value)` at the top of `render()`, every `handle*`, every `createComponent*` and every form `onSuccess`, written inline (`BabysitterDocumentsControl`) or through a private helper (`TurnusDocumentsControl::assertCanManage()`).

## 4. Autosave: one signal that writes to many tables

Detail pages save single fields by AJAX. It is the widest write path in the application and deserves a full read whenever a module with autosave is in scope.

Flow: `www/js/autosaveForm.js` posts `__autosave_context`, `__autosave_field`, `__autosave_value` (or `__autosave_checked`, `__autosave_item_id`) and `id` → `AdminPresenter::handleAutosavePartial()` (directly as `?do=autosavePartial`, or from a form's `onSuccess` through `tryHandleAutosavePartialRequest($expectedEntityId)`) → `AutosaveFieldUpdateService::tryHandleRequest()`.

The script sets `_do=autosavePartial`, so in normal use the request goes to the presenter's signal and never reaches the form or the control the field sits in. On that path the control's own `isAllowed` and its owner-scoped lookup do not run. What stands between the request and the row is the presenter's resource and the layers below. The same form can also be submitted whole, without the autosave fields, and then only the control's `onSuccess` runs: two write paths to the same columns, which can differ in what they validate and what they log.

Layers, in order:

1. AJAX + POST, and `isFrom([FetchSite::SameSite, FetchSite::SameOrigin])`.
2. `getAllowedAutosaveContexts()`: a `match` on the presenter name. A context is accepted only on the presenter it is listed for.
3. `validateAutosavePartialRequest($context, $entityId)`:
   - `documents.*`: the document must be an active row whose `dir` is `<folder>/<route id>`.
   - contexts in `getCurrentEntityAutosaveContexts()`: the posted `id` must equal the route `id`.
   - contexts in neither list get **no** ID check here. They rely on an override in the presenter (`TodoPresenter`, `UserManagementPresenter` have one). When a context is in scope, establish which of the three cases it falls into.
4. `AutosaveFieldUpdateService::createDefinitions()`: per context, the table and the map of field name → column, with the type of each field. Only listed fields are written. It loads the row by `table->get($entityId)`, so every ownership rule has to hold before this point.

What to try: a context of another presenter; a listed context with a foreign `id`; a field that is in the definition but that this role should not change (a status, an owner, a flag); a select value outside its option table; `__autosave_item_id` of a junction row belonging to another record.

## 5. Where things are

| Need | Location |
|---|---|
| Forms | `app/Model/Form/Factory/BaseFormFactory.php` (`create()`, `createDefaultForm()`), the project's one way to create a form |
| Confirmed create / delete forms | `app/UI/Admin/Form/ConfirmedCreateFormFactory.php`, `ConfirmedDeleteFormFactory.php` |
| Module forms | `app/UI/Admin/Form/<Module>/<Form>/<Form>FormFactory.php` |
| Documents (`sn_files`) | `app/Model/Repository/FileRepository.php`: `findDocuments($dir, $ownerId)`, `findDocument($dir, $ownerId, $id)` are owner-scoped; `updateDocument($id, …)` and `softDelete($id)` take a bare ID and trust the caller to have verified it |
| Base repository | `app/Model/Repository/BaseRepository.php`: `insert(array)`, `update(int $id, array)`, `getItem(int)` |
| Storage paths | `app/Model/DataProvider/Directory/StorageDirProvider.php` (values in `config/parameter.neon`), `DirectoryProvider` for `rootDir` |
| Private files served by PHP | `handleDownload` of the five `*DocumentsControl` classes, `BabysitterVideosControl` (`play`, `download`), `BabysitterPresenter::handleDownloadPdf`, `handleDownloadHhsPresentation` |
| Photos gated by a cookie | `app/Model/Security/MediaAccessCookie.php` + `www/img/users/.htaccess`, `www/img/opatrovatelka/.htaccess` |
| Files under the web root | `www/documents`, `www/export`, `www/uploads`. `www/.htaccess` forbids `documents/` and `export/` with a rewrite rule; `security-scan.php status` prints the rule it finds for each folder |
| Audit log | `app/Model/Service/Audit/ChangeAuditLogger.php` (`logCreated`, `logDeleted`, `logDocumentUploaded`, `logDocumentDeleted`), `ChangeLogRepository` |
| Login, session | `app/UI/Login/`, `app/Model/Security/AdminAuthenticator.php`, `config/security.neon` |
| Environment and secrets | `config/.env*`, `App\Model\Settings\EnvironmentFileLoader`, `App\Bootstrap::loadEnvironment()` |
| Earlier security work | `workflow/_active/zabezpecenie-prihlasovania/`, `workflow/_active/authorizator/`, `workflow/cybersecurity/` (read before auditing `login` or `config`, so settled points are not raised again) |

## 6. Working with the test suite

PHPUnit, bootstrap `tests/bootstrap.php`, `APP_ENV=test`, its own database `opatrovatelky_nette_test` on port 3307 that `DatabaseTestCase::setUp()` truncates. Run it with the PHP 8.5 binary; the system PHP is 8.2 and gives false errors.

```bash
/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/phpunit --filter DocumentFormHttpTest
/c/wamp64/bin/php/php8.5.0/php.exe vendor/bin/phpunit
```

| Helper | Use |
|---|---|
| `tests/Support/PHPUnit/DatabaseTestCase.php` | base class; clean database per test, `getDatabase()`, `getContainer()` |
| `tests/Support/PHPUnit/PresenterWorkflowTrait.php` | `loginAs(UserRole $role, ?int $id)`, `logout()`, `runPresenter($name, $params)` (GET, returns the response), `renderPresenter($name, $params)` (returns HTML) |
| `tests/Support/Database/TestDatabase.php` | `createUser`, `createBabysitter`, `createFamily`, `createPartner`, `createAgency`, `createTurnus`, `createCountry`, `createFile`, `createChangeLog`, `insert($table, $data)`, `pdo()` |
| `tests/Support/Http/TestWebClient.php` | a real `php -S` server with a cookie jar: `login`, `get`, `post`, `submitForm($path, $formId, $fields)`, `submitUpload`, `stop` |
| `tests/Support/PHPUnit/ConfirmedFormWorkflowTrait.php`, `tests/Support/Http/FakePostRequest.php` | submitting confirmed forms and faking a POST in-process |

Where a new security test goes: `tests/Api/` for signals and AJAX endpoints run in-process, `tests/E2E/` for real HTTP with forms, uploads and downloads, `tests/Smoke/` for "every presenter" style checks, `tests/Regression/` for a hole that was found and closed. Extend an existing test class or data provider when the new case fits one; several of them already iterate over all document controls or all presenters.
