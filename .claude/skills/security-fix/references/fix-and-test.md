# Fixes, tests and the ledger

How to close a finding the way this project already does it, how to prove the hole with a test, and the template of the audit ledger. Open the named reference file and copy its shape; the snippets here are reminders, not the source of truth.

## Contents

1. Fix recipes
2. Test recipes
3. Ledger template

## 1. Fix recipes

Before writing new code, look for a service, repository method or trait that already does it (`app/Model/Service`, `app/Model/Utils`, `app/Model/Repository`). When the same fix lands in a second class within the scope, extract it instead of copying.

### Permission in a control

Reference: `app/UI/Admin/Control/Babysitter/BabysitterDocuments/BabysitterDocumentsControl.php` (inline) and `Control/Turnus/TurnusDocuments/TurnusDocumentsControl.php` (private `assertCanManage()`).

Inject `Nette\Security\User` and refuse at the top of `render()`, every `handle*`, every `createComponent*` and inside every form `onSuccess`:

```php
if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
	$this->getPresenter()->error('Prístup zamietnutý', 403);
}
```

Choose the resource from the module the control belongs to and confirm it against `AuthorizatorFactory::ROLE_RESOURCES`. If the control serves several modules or it is unclear which roles use it today, that is a decision for the user, not a guess.

A control that only renders part of the layout for everyone (menu, toolbar) should render nothing for a role without the resource instead of raising 403, or it breaks every page. `tests/Smoke/OffcanvasAccessSmokeTest.php` shows the expected behaviour.

### Permission in a presenter

Return the resource from `getResource()`. For an operation that needs more than the module resource, add an explicit check at the start of the action or signal and answer `$this->error('Prístup zamietnutý', 403)`. For a presenter open to every signed-in user, each entry point decides for itself (`UserManagementPresenter::canEditUser()`, `canEditAccess()`).

### Load through the owner

Reference: `FileRepository::findDocument($dir, $ownerId, $id)` and its use in `handleDownload()` / `handleDelete()`.

The owner ID is the one the server verified (set by the presenter from the route after its own 404 check, passed to the control with `setContext()` or the factory). The child is looked up inside that owner and a miss is a 404:

```php
$document = $this->fileRepository->findDocument($this->dir, $this->babysitterId, $id);
if ($document === null) {
	$this->getPresenter()->error('Dokument neexistuje.', 404);
}
```

If the repository has only a bare-ID method, add an owner-scoped finder to the repository (a query with both conditions) instead of comparing in the control. Keep a control from acting when its context was never set: an owner ID of 0 must not match anything.

### Write the verified record, not the posted one

Reference: `BabysitterDocumentsControl::createComponentDocumentForm()`.

Capture the ID of the verified record when the form is built and use that variable in `onSuccess`. The hidden field may stay for the JavaScript, but the handler does not read it:

```php
$documentId = (int) $document['id'];
$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($documentId): void {
	// permission check, then:
	$this->fileRepository->updateDocument($documentId, [
		'notice' => (string) $values->notice,
		'status' => (int) $values->status,
	]);
};
```

### Whitelist of written fields

Name each column in the handler or repository call, with its cast. Replace `(array) $values` and whole-variable `update()` / `insert()` with an explicit array. A value that selects a column, an order or a table is mapped through a `match` to constants; an unknown value is rejected, not passed through.

For autosave: a context is added in three places that must agree: `AdminPresenter::getAllowedAutosaveContexts()` for its presenter, either `getCurrentEntityAutosaveContexts()` or an override of `validateAutosavePartialRequest()` for the ID check, and `AutosaveFieldUpdateService::createDefinitions()` for the fields. A field that not every visitor of the page may change needs a check in the presenter's `validateAutosavePartialRequest()` override, as `UserManagementPresenter` does for `user.access`.

### State change through a form

Create forms with `BaseFormFactory`, the project's one way to build a form. Replace a deleting GET link with `ConfirmedDeleteFormFactory` (a POST form with a confirmation); `tests/Regression/AdminDeleteFormRegressionTest.php` shows the expected behaviour. A custom AJAX handler starts like `AdminPresenter::handleAutosavePartial()`: AJAX + POST, then `isFrom([FetchSite::SameSite, FetchSite::SameOrigin])`.

### Context that was never set

A control whose owner ID defaults to 0 and is filled by `setContext()` refuses to write while it is 0: answer 404 at the top of every `handle*`, `createComponent*` and `onSuccess` that writes or reads through the owner, next to the permission check. The read paths usually fail closed already (nothing matches owner 0); uploads and inserts do not.

### Upload

Reference: `createComponentUploadForm()` in `BabysitterDocumentsControl`. In order: `isOk()` and `hasFile()`, extension from a constant whitelist (lower-cased), size against a constant, content type only as an additional filter, generated name (`Strings::webalize` + `Random::generate(8)` + the whitelisted extension), target directory from `DirectoryProvider` + `StorageDirProvider` + the verified owner ID, database row with `$this->user->getId()`, then `ChangeAuditLogger::logDocumentUploaded()`. Images go through `App\Model\Utils\Validator\ImageValidator`.

### Sending a file

Build the path from the provider directory, the verified owner ID and the `name` column of the row loaded through that owner; check `is_file()`; send with `FileResponse($path, $nameFromDatabase)`. Nothing from the request is concatenated into the path.

### Output

Remove `|noescape` where the value is plain text.

A colour in a `style` attribute cannot simply lose the filter: `style="background-color:{$color}"` is escaped for CSS and `#8A2062` becomes `\#8A2062`. The form the project already uses builds the whole attribute with `n:attr`, which escapes it as an attribute only:

```latte
<span class="badge" n:attr="style: $color !== '' ? 'background-color:' . $color">{$label}</span>
```

Reference: `Control/Babysitter/BabysitterList/templates/BabysitterListControl.latte`. Pair it with validation of the value: at the write side (the profile form and the autosave definition of the colour field), and, while old values exist, at the read side the way `MissingCalendarBuilder` does it (`/^(#[0-9a-f]{3,8}|[a-z]{3,20})$/i`, otherwise a default colour). Which formats to accept is the user's decision when existing rows hold values the stricter rule would reject; check what the column holds before proposing.

JSON for the browser returns named fields, and the script inserts them with `textContent`.

### Audit trail

Role and access changes, deletions, uploads and document removals call `ChangeAuditLogger`. A fix that adds a new write path adds its log call. Where a form's `onSuccess` writes the same columns autosave writes, it logs the same way autosave does, so the trace does not depend on which path the request took.

## 2. Test recipes

A new or fixed entry point gets three cases: a foreign ID, a role without the permission, a guest. Add a fourth for an extra field or context when the entry point writes. Each test asserts both the refusal and that the database row did not change; a 403 that arrives after the write is still a hole.

Extend an existing class or data provider when the case fits (several iterate over all presenters or all document controls). Base class `Tests\Support\PHPUnit\DatabaseTestCase`, trait `PresenterWorkflowTrait`, fixtures from `TestDatabase`.

### Guest

Reference: `tests/Smoke/GuestAccessSmokeTest.php`. It globs every admin presenter, so a new presenter is covered automatically. For a signal or AJAX endpoint, `logout()` and assert the redirect to `/login/` (for AJAX the JSON payload carries `redirect`) and an unchanged row, as `UserAccessAutosaveContractTest::testGuestIsSentToTheLoginPageAndChangesNothing()` does.

### Role without the permission

Use a real role when one lacks the resource under `ROLE_RESOURCES` (`loginAs(UserRole::DEALER_JUNIOR)` against a partner page).

When no real role below admin lacks it, take the resource away for the test. Reference: `tests/Smoke/OffcanvasAccessSmokeTest.php`:

```php
$user = $this->getContainer()->getByType(User::class);
$this->originalAuthorizator = $user->getAuthorizator();
$acl = $this->getContainer()->getByType(AuthorizatorFactory::class)->create();
$acl->deny(UserRole::DEALER->value, Resource::FAMILY->value);
$user->setAuthorizator($acl);
$this->loginAs(UserRole::DEALER);
```

Restore the original authorizator in `tearDown()`; the container is shared by all tests. This technique is what proves a control checks for itself: the presenter still lets the role in when only the control's resource is denied, or the control is exercised on a presenter with a different resource.

### Foreign ID through a signal (in-process)

Reference: `tests/Api/UserAccessAutosaveContractTest.php`. Build the HTTP request, swap it into the container, run the presenter with the signal:

```php
$request = new HttpRequest(
	new UrlScript('https://opatrovatelky.local/'),
	['id' => (string) $foreignId, /* post fields */],
	[],
	[Helpers::StrictCookieName => '1'],          // same-site cookie, or the signal is refused as cross-site
	['X-Requested-With' => 'XMLHttpRequest'],
	'POST',
);
$container->removeService('http.request');
$container->addService('http.request', $request);

$response = $presenter->run(new ApplicationRequest('Admin:Babysitter', 'POST', [
	'action' => 'update',
	'id' => $ownPageId,
	Presenter::SignalKey => 'babysitterDocuments-delete',
	'babysitterDocuments-id' => $foreignId,
]));
```

Refusals arrive either as a `BadRequestException` with the HTTP code or as a JSON payload with a status code; the reference test handles both. Remove the swapped `http.request` service in `tearDown()`.

### Forged hidden field or upload over real HTTP

Reference: `tests/E2E/DocumentFormHttpTest.php` and `FileUploadHttpTest.php`. `TestWebClient` starts a real server, signs in, and `submitForm($path, $formId, $fields)` fetches the page, takes the form with its CSRF token and posts it with the given fields overridden:

```php
$response = $this->web->submitForm(
	'/babysitter/update/' . $ownerId . $tab,
	'frm-babysitterDocuments-documentForm-' . $ownId,
	['id' => (string) $foreignId, 'notice' => 'tampered', 'status' => '0'],
);
self::assertSame($foreignBefore, $this->fileRow($foreignId));
```

Create the foreign record under another owner and, where possible, another module, so the test fails for any lookup that ignores the owner. Stop the client in `tearDown()`.

### Extra field or context

Post a field the form does not declare, a privileged column (`permission`, `active`, an owner), or an autosave context of another presenter, and assert the row is unchanged and no change-log row was written (`ChangeLogTableMap::TABLE_NAME` count is 0). `tests/Api/AutosavePartialContractTest.php` has the autosave shape.

### Output

Store the hostile value in the fixture (`<script>alert(1)</script>`, `" onmouseover="x`, `red;background:url(//x)`), render the page with `renderPresenter()`, and assert the raw string is absent from the HTML. A render test also satisfies the project rule that a changed template is proven by rendering it.

## 3. Ledger template

Create `workflow/_active/security-audit/ANALYZA_A_WORKFLOW.md` on the first run. Slovak with diacritics, conclusion first. `coverage.json` beside it belongs to `security-scan.php`; never edit it by hand.

Seed „Rozhodnuté“ from the user's standing decisions; ask nothing about them and do not list them as findings.

```markdown
# Bezpečnostný audit aplikácie

**Záver (<dátum>):** <jedna až dve vety: čo je prejdené, koľko nálezov je otvorených, čo je najvážnejšie>. Hodnotenie: <X>/10 (až keď sú prejdené jednotky pod `app/UI`; dovtedy „zatiaľ bez hodnotenia, prejdených N z M súborov“).

## Rozhodnuté (nehlásiť znova)

- Statický PIN ako druhý faktor: zamietnutý. Druhý faktor je zatiaľ len návrh (`workflow/_active/zabezpecenie-prihlasovania/`).
- Heslá v git histórii: odložené, repozitár je súkromný.
- Fotky pod `www/img`: ochrana len cez `.htaccess` a cookie, nie cez PHP.
- <ďalšie rozhodnutia s dátumom>

## Pokrytie

Aktuálny stav vypíše `security-scan.php status`. Tu je, čo sa v ktorej jednotke našlo.

| Jednotka | Prejdené (dátum, pri časti jednotky „3 z 15 súborov“) | Nálezy | Opravené | Otvorené | Poznámka |
|---|---|---|---|---|---|

## Nálezy

| ID | Závažnosť | Miesto | Útok | Dopad | Oprava | Stav |
|---|---|---|---|---|---|---|

## Čaká na rozhodnutie

- **SEC-NNN** <otázka, možnosti a čo ktorá možnosť komu zmení>
  Rozhodnutie: <prázdne, kým používateľ neodpovie; potom jeho odpoveď a dátum>

## Overené ako bezpečné

Jedna veta na vstupný bod, ktorý vyzeral podozrivo a nie je: prečo. Ďalší beh sa k nemu nevracia.

- `cesta/Súbor.php::metóda` — <dôvod>

## Stav prác

| Dátum | Rozsah | Výsledok | Kontroly |
|---|---|---|---|
```

Rules for editing it:

- Re-read the file immediately before each edit and change only the lines of this run. Other sessions work in the same file.
- A finding keeps its ID for good. When it is fixed, change its „Stav“ to „opravený“ and name the proving test in „Oprava“; do not delete the row.
- Update the „Záver“ sentence and the rating when the picture changes, not on every run.
