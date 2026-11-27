# Attack checklist

What to try against an entry point, grouped by what the entry point does. Read the sections that match; skip the rest. Each item says what the attacker does and what safe code looks like here.

Inventory labels in `code font` (for example `bare-id-load`) are the labels `security-scan.php` prints in its "risky constructs" column, so a label in the inventory points to its section here.

## Contents

1. Permission on the entry point
2. Record identity (IDOR)
3. Writes and field whitelists
4. State changes and request origin
5. Files: upload, download, paths
6. Output: HTML, JavaScript, documents
7. SQL
8. Data returned to the browser
9. Business rules and audit trail
10. Dangerous functions
11. Login and session (target `login`)
12. Configuration and web root (target `config`)
13. Front-end scripts

## 1. Permission on the entry point

Applies to every `action*`, `handle*`, `render*`, `createComponent*` and form handler.

- **Call it as the lowest role.** Find the check that refuses a `dealerJunior` who lacks the resource. For a presenter it is `getResource()` + `startup()`; for a control it must be in the control, because a control can be attached to a presenter with a weaker resource, and the layout components are on every page.
- **Call it as a guest.** `AdminPresenter::startup()` redirects. Anything not extending `AdminPresenter` needs its own answer.
- **Signal on the wrong action.** `?do=delete` works on `/babysitter/` as well as on `/babysitter/update/5`. A handler that relies on state set in `actionUpdate()` (a `$this->babysitterId` that stays 0, a context never set; label `context-default-zero`) must fail closed, not act on ID 0 or on "all". Try each write of the control on an action that does not set the context: an upload there creates a file and a row that belong to no record.
- **Component nobody rendered.** A form factory guarded by `{if $canManage}` in the template still answers a POST. The factory and the `onSuccess` need the check.
- **Presenter with `getResource()` null.** Every action and signal needs an explicit decision (`UserManagementPresenter` is the example).
- **Sub-feature resources.** An operation meant for `FAMILY_MANAGEMENT`, `WORKER_MANAGEMENT`, `TODO_VIEW_ALL` or `PARTNER_FAMILY` reached by a role that has only the module resource.
- **Check in the form handler, not only the factory.** `createComponent*` runs when the form is built; the role or the record can differ by the time `onSuccess` runs in another request. The project pattern checks in both.
- **Wrong resource.** A control copied from another module that still checks the other module's resource.
- **Role from the request.** No role, permission level, `user_id` or owner may come from a parameter, a hidden field or a form value. Only `$this->user`.

## 2. Record identity (IDOR)

Labels: `bare-id-load`, `bare-id-write`, `hidden-field`, `raw-request`, `context-default-zero`.

- **Change the ID in a signal argument** (`component-id=12` → another row). Safe: the record is loaded through the owner the server already verified, as `FileRepository::findDocument($dir, $ownerId, $id)` does, and a miss is a 404.
- **Change a hidden field.** Safe: the handler writes to the ID captured when the form was built from a verified record, never to `$values->id`. `BabysitterDocumentsControl::createComponentDocumentForm()` shows it. For every `addHidden` check whether the handler reads the value at all. A hidden field that carries anything other than an ignored ID (a status, a type, an owner) is a free-text input: the handler accepts whatever is posted unless it validates it.
- **Change the Multiplier key** (`documentForm-12` → `documentForm-99`). Safe: the multiplier closure loads the row through the owner and 404s.
- **Repository methods taking a bare ID** (`updateDocument($id)`, `softDelete($id)`, `BaseRepository::update($id)`, `getItem($id)`, `table->get($id)`): legitimate only when every caller verified the ID first. Trace each caller.
- **Mismatch between the route ID and the posted ID.** The page is `/family/update/7`, the POST says `id=8`. Which one is written? Autosave has an explicit check for this; other forms need their own.
- **Second-level children.** A row of a junction table (`__autosave_item_id`, a turnus of a family, an entry of a registry) belonging to another parent than the one in the URL.
- **Records that belong to one user**: todo items (`TodoPresenter::getAllowedTodo`), the own profile and password, account access. Try the ID of a colleague.
- **Exports and generated files addressed by ID**: `handleGeneratePdf(int $id)`, `handleDownloadPdf`, HHS presentations, files named `<id>.pdf` in an export folder. The ID must pass the same check as the detail page, and the path must be built from the verified ID, not from a parameter string.
- **404 or 403, not a redirect to a default record.** A handler that falls back to "first row" on a bad ID is a leak.
- **Existence oracle.** Different answers for "exists but not yours" and "does not exist" let IDs be enumerated; prefer the same 404 for both.

## 3. Writes and field whitelists

Labels: `write-whole-variable`, `values-as-array`, `form-trust`, `hidden-field`.

- **Extra field in the POST.** Safe: columns are named one by one in the handler. Suspicious: `(array) $values`, `$form->getValues('array')`, or a whole variable passed to `update()` / `insert()`. Follow the array to the query and list which keys can arrive. A form's own values are limited to its declared controls, so the question becomes: which controls does the form declare, and is every one of them a column this role may write?
- **Fields added by JavaScript or left over from a wider form.** `getHttpData()`, `getUntrustedValues()` and `$request->getPost()` bypass the form's control list and validation.
- **Select, radio and checkbox list values outside the offered options.** Nette rejects them by default; `checkDefaultValue(false)` turns that off, and values read from raw POST never had it. For autosave, the definition's select type has to verify the value against its option table.
- **Disabled and read-only inputs.** A disabled control is omitted from values; a control that is merely `readonly` in HTML is not. `setOmitted(false)` brings a disabled control back.
- **Privileged columns**: role / `permission`, `active`, owner or `user_id`, approval and payment flags, prices, timestamps, soft-delete flags. Find every path that can write them and check that it has its own permission test (`UserManagementPresenter::canEditAccess` is the model).
- **Autosave**: the context must be listed for this presenter, the field must be in the definition, and the context must have an ID check (see `project-map.md` section 4). A field present in the definition is writable by everyone who can open that page.
- **Two write paths to the same columns.** A form whose fields autosave can also be posted whole. Compare the two: a field the autosave definition leaves out but the form's `onSuccess` writes, a value one path validates and the other does not, a change one path logs and the other does not (label `write-no-audit`).
- **Names from the request used as identifiers**: a column to sort by, a table, a field name used to build a column, a direction. Safe: mapped through a fixed `match` or array to constants.
- **Type and range**: negative amounts, zero or huge numbers, dates in the wrong order, strings far beyond the column length, arrays where a scalar is expected (`field[]=x` turns a string into an array and can raise a type error or skip a comparison).

## 4. State changes and request origin

Labels: `signal-link`, `destructive-get-link`, `form-without-factory`, `cross-origin`.

What the framework already refuses is in `project-map.md` section 1. In short: signals and form submits from another origin are dropped, so the remaining risk is on the application's own origin.

- **Change through a GET link** (`destructive-get-link`): `{link delete!, id: …}` with only a JavaScript `confirm()`. Another site cannot trigger it, but a link on the application's own pages, a pasted address, a browser prefetch or the back button can, and the address stays in history and logs. Destructive actions belong in a POST form (`ConfirmedDeleteFormFactory`). Grade by the result: a hard delete or one that leaves no audit entry is Stredná; a soft delete that is logged and that the role is allowed to do anyway is Nízka.
- **Form created without `BaseFormFactory`** (`form-without-factory`): breaks the project convention. It still has the framework's same-origin check, so it is a hole only if something else is missing with it.
- **Custom AJAX endpoint** that is neither a signal nor a form (an action that writes, a handler reading raw POST): must require POST and `isFrom([FetchSite::SameSite, FetchSite::SameOrigin])`, as `handleAutosavePartial()` does.
- **`#[CrossOrigin]`, `#[Requires(sameOrigin: false)]`, `allowCrossOrigin()`** must not appear.
- **An action (not a signal) that changes state**: `action*` methods get no origin check at all. Anything that writes in an `action*` or `render*` method is reachable by a plain cross-site GET.
- **Repeat and replay**: submit the same create or pay request twice quickly; a one-time action that can be replayed (a token, an approval).

## 5. Files: upload, download, paths

Labels: `upload`, `file-send`, `file-op`, `dynamic-include`, `zip`.

Upload:

- **Extension**: a whitelist, compared on the lower-cased real extension. Try `shell.php`, `x.pdf.php`, `x.php.pdf`, `x.phtml`, `x.phar`, `.htaccess`, a name with a trailing dot or space, upper case.
- **Stored name** generated by the server (`webalize` + random + whitelisted extension). The original name is never used as a path.
- **Location** under the private directory from `StorageDirProvider`, outside `www/`. A file stored under the web root is executed or served by Apache without any PHP check.
- **Size** limited in PHP, not only by a `data-max-size` attribute.
- **Content type**: `FileUpload::getContentType()` is detected from the file's first bytes, not taken from the browser, but a file can start with the right bytes and be something else. It may narrow, never grant; the extension whitelist and the storage location are what protect. Images go through `ImageValidator`.
- **Owner** of the stored row: the record verified by the server and `$this->user->getId()`, not IDs from the form.

Download and serving:

- **Path** built from the provider directory + owner ID verified by the server + the `name` column of a row loaded through that owner. Try `../` in any parameter that reaches a path, an absolute path, a NUL or encoded slash.
- **Permission and ownership** checked in the handler that sends the file, the same as for the page.
- **Response name and type**: the download name comes from the database row; an HTML or SVG file sent inline from the application's origin runs script there, so user files are sent as attachments.
- **Files reachable without PHP**: anything under `www/` that is not blocked. `www/.htaccess` forbids `documents/` and `export/`; the image folders have their own `.htaccess` with the cookie gate (settled, see the ledger). Check that a new or changed folder under `www/` is covered by a rule, and that the rule holds for the paths actually used (`security-scan.php status` prints what it finds).

Other file operations:

- **Delete, rename, copy** with a name from the request.
- **Generated files** (PDF, DOCX, PPTX, exports): written to a path derived from a verified ID, not from user text; temporary files removed; no user-controlled file name in a shell command.
- **Archives**: extraction of an uploaded archive without checking entry names (zip slip).

## 6. Output: HTML, JavaScript, documents

Labels: `noescape`, `nocheck`, `raw-html`, `inline-js-variable`, `dom-html`.

Latte escapes by context, so the leads are the places where that is switched off or bypassed.

- **`|noescape`**: where does the value come from, and can any user write it? Find the write path and what it validates. Then render the hostile value before grading, because what `noescape` lets through depends on the context:
  - In element content it prints raw markup. User-writable text there is script in every reader's browser.
  - In a quoted attribute, Latte 3.1 still converts the quote character, so the value cannot leave the attribute. In `style="background-color:{$color|noescape}"` what remains is injected CSS, limited further by the column width. The colours come from the user's own profile, where they are free text.
  - The reason `noescape` is on those colours: without it Latte escapes for CSS and `#8A2062` comes out as `\#8A2062`. Removing the filter breaks the page; the working form is in `fix-and-test.md`.
  - Translations printed with `noescape` into the PDF template are writable by whoever holds `TRANSLATION`.
- **`|nocheck`** on a URL attribute, or a URL from user data in `href` / `src` built in PHP.
- **`Html::fromHtml()`, `setHtml()`** with user data; flash messages and form errors that include user input as HTML.
- **Variables inside inline event handlers** (`onclick="…{$name}…"`).
- **JSON consumed by scripts** that insert it with `innerHTML`, `insertAdjacentHTML` or jQuery `.html()`. The server escaped nothing because it was JSON; check `www/js` and the `<script>` blocks of templates (label `dom-html`) for how search results and previews are inserted. Constant strings are fine; anything that came from the server or from an input is not.
- **Stored input shown to a higher role**: a note typed by a `dealerJunior` and read by an admin is the valuable direction for XSS here.
- **HTML rendered to PDF**: user text in the PDF template can carry `<img src="file:///…">` or remote URLs that the PDF library fetches on the server.
- **XML and Office documents** built by string concatenation (`PresentationXmlPart` and similar): unescaped `&`, `<` break the document or inject parts.
- **Spreadsheet and CSV exports**: a cell starting with `=`, `+`, `-`, `@` is a formula in Excel.

## 7. SQL

Labels: `raw-sql`, `dynamic-order`.

- **Values** always go as parameters (`query($sql, …$params)` or Explorer `where('col ?', $value)`). Look for a variable interpolated into the SQL string that is not a `TableMap` constant or a table alias.
- **Identifiers and keywords**: `ORDER BY $x`, `$direction`, `LIMIT $n`, a column or table chosen at run time. Trace the variable to its origin. Safe: it is selected from a fixed list by a `match`; unsafe: it carries text from a filter, a persistent parameter or a POST field.
- **`WHERE` assembled from parts** (`implode(' AND ', $where)`): each part must use placeholders; check the parts that are added conditionally from filters.
- **Explorer calls with a variable in the column position**: `where($column, $value)`, `order($order)`, `select($columns)`.
- **`LIKE` with user text**: `%` and `_` are wildcards; harmless for integrity, but an unescaped search can be made very slow.
- **Filters as a data leak**: a list filter that accepts an owner or module ID the role should not see.

## 8. Data returned to the browser

Label: `json-out`.

- **Whole rows in JSON** or in `data-` attributes: password hashes, e-mails, phone numbers, internal notes, columns the page does not display. Return named fields.
- **Search, autocomplete and side panels** returning records of modules the role has no resource for. Each result type needs its own `isAllowed`.
- **Rendered but hidden**: data placed in the HTML and hidden by CSS or a collapsed tab is delivered.
- **Error output**: exception messages, SQL, file paths sent to the client; Tracy enabled outside development.
- **Lists that ignore the role's scope**: a todo list without `TODO_VIEW_ALL` showing everyone's items when a filter parameter is changed.
- **Snippets and AJAX partials** rendering a component the full page would not render for this role.

## 9. Business rules and audit trail

- **State transitions** skipped or reversed by posting a status the UI does not offer (closing someone's task, reopening a paid item, marking a document valid).
- **Deleting a record others depend on**, or deleting as a role that may only edit.
- **Changes that should leave a trace** (label `write-no-audit`): role and access changes, deletions, document uploads and removals go through `ChangeAuditLogger`, and autosave logs every field through `ChangeLogRepository::logChange`. A write path that bypasses both (a form `onSuccess` calling a repository `update*` directly) lets an employee change data without evidence, even though the same change made by autosave would be logged.
- **Who is recorded as the author**: always `$this->user->getId()`, never a form value.
- **Heavy operations without a limit**: PDF or presentation generation, exports and searches that a signed-in user can fire in a loop. There is no cron or queue in this project, so the cost is paid inside the request; note it as Nízka unless it can take the site down.

## 10. Dangerous functions

Labels: `command`, `unserialize`, `eval`, `xml`, `outbound-http`, `redirect-url`, `weak-random`, `superglobal`.

- **`exec`, `shell_exec`, `proc_open`, backticks** (document conversion tools are the usual place): no request data in the command; arguments through `escapeshellarg`; file names generated by the server.
- **`unserialize`** of anything a user can influence, including values stored in the database or a cookie.
- **XML parsers** on uploaded files (DOCX, PPTX, SVG): external entities must stay disabled; never pass `LIBXML_NOENT`.
- **Outbound requests** to a URL that contains user input (server-side request forgery).
- **Redirects** to a URL from a parameter (`redirectUrl`, a back-link); `restoreRequest()` only with a key the application issued.
- **`md5`, `sha1`, `rand`, `mt_rand`, `uniqid`** used for a token, a password, a file name meant to be unguessable, or a reset link. Passwords use `Nette\Security\Passwords`; tokens use `Random::generate()` or `random_bytes()`.
- **Superglobals** instead of the request object.
- **Comparisons**: `==` between a secret and user input, `in_array` without `true`, `strcmp` on arrays.

## 11. Login and session (target `login`)

Read `workflow/_active/zabezpecenie-prihlasovania/ANALYZA_A_WORKFLOW.md` first; many points were assessed and several were decided. Report only what is new or regressed.

- **A role change or a deactivation takes effect when?** The identity and its roles are stored in the session at login. If nothing reloads them, a demoted or deactivated employee keeps the old rights until the session ends. Look for an `IdentityHandler` (`wakeupIdentity`) or a per-request check of `active` and `permission`.
- **Session ID regenerated at login**, session cookie `HttpOnly`, `SameSite`, and `Secure` in production.
- **Rate limiting** per account and per address; equal responses and timing for unknown user and wrong password.
- **Password change**: requires the current password, or a permission to change someone else's; other sessions of that account end.
- **Back-link after login** (`storeRequest` / `restoreRequest`) cannot be pointed at another site.
- **Logout** is a POST with protection and destroys the session.
- **Password storage**: `Passwords::hash`, rehash on login; legacy hashes upgraded.
- **Media cookie** (`MediaAccessCookie`): cleared on logout, not valid forever, not derivable.

## 12. Configuration and web root (target `config`)

- **Debug mode and Tracy** off outside development; no way to turn them on by cookie or IP in production config.
- **Response headers**: `X-Frame-Options` or CSP `frame-ancestors`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, HSTS in production, a Content-Security-Policy.
- **Session settings** in `config/*.neon`: cookie flags, expiration, save path.
- **`.htaccess`**: directory listing off, no script execution in upload and document folders, dot-files and `.env` denied, what `Require` each protected folder states.
- **Entry scripts in `www/`** other than `index.php`; leftover tools, dumps, backups, `phpinfo`.
- **Secrets**: only in `config/.env` (git-ignored); none in PHP constants, `.neon` files, JavaScript or committed test files beyond the fake ones in `.env.test`.
- **Dependencies**: `composer audit`.
- **Folders that must not be reachable**: `temp/`, `log/`, `private/`, `config/`, `vendor/`, `.git/`.

## 13. Front-end scripts

Labels: `dom-html`, `ajax-call`, `client-storage`.

- **HTML built from server data** in `www/js` (see section 6).
- **Every endpoint a script calls** (`ajax-call`) is an entry point; make sure it is in the inventory under its presenter or control.
- **Rules enforced only in JavaScript**: size limits, allowed extensions, disabled buttons, hidden options. Each needs its server twin.
- **Sensitive values kept in the page**: tokens, other users' IDs, internal flags in `data-` attributes, `localStorage` or a readable cookie.
