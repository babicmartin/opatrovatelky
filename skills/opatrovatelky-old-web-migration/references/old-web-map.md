# Old Web Map

Legacy base path: `C:\wamp64\www\monika\opatrovatelky\old`

The old CRM uses simple `?page=` routing through `index.php`, Router, Controller, and PHP templates. The menu comes from `sany_pages` with `active`, `parent`, `permission`, `position`, `in_menu`, `url`, and `name`. Authentication uses session state and old SHA-256 `crypt($password, '$5$')` hashes.

## Pages

| Old page | Controller | Template | Update page | Old model | New target |
| --- | --- | --- | --- | --- | --- |
| homepage | `mvc/controller/homepage.php` | `template/pages/homepage.php` | - | - | `Admin:Home` |
| opatrovatelky | `mvc/controller/opatrovatelky.php` | `template/pages/opatrovatelky.php` | opatrovatelky-update | `mvc/babysitter/Babysitter.php` | `Admin:Babysitter` |
| families | `mvc/controller/families.php` | `template/pages/families.php` | families-update | `mvc/family/Family.php` | `Admin:Family` |
| partneri | `mvc/controller/partneri.php` | `template/pages/partneri.php` | partneri-update | - | `Admin:Partner` |
| turnus | `mvc/controller/turnus.php` | `template/pages/turnus.php` | turnus-update | `mvc/turnus/Turnus.php` | `Admin:Turnus` |
| turnus-records-month | `mvc/controller/turnus-records-month.php` | `template/pages/turnus-records-month.php` | - | - | `Admin:Turnus:month` |
| turnus-select-month | `mvc/controller/turnus-select-month.php` | `template/pages/turnus-select-month.php` | - | - | `Admin:Turnus:selectMonth` |
| projekty | `mvc/controller/projekty.php` | `template/pages/projekty.php` | - | - | `Admin:Project` |
| opatrovanie | `mvc/controller/opatrovanie.php` | `template/pages/opatrovanie.php` | opatrovanie-update | - | `Admin:Care` |
| agencies | `mvc/controller/agencies.php` | `template/pages/agencies.php` | agencies-update | - | `Admin:Agency` |
| country | `mvc/controller/country.php` | `template/pages/country.php` | country-update | - | `Admin:Country` |
| todo | `mvc/controller/todo.php` | `template/pages/todo.php` | todo-update | `mvc/todo/Todo.php` | `Admin:Todo` |
| pracovnici | `mvc/controller/pracovnici.php` | `template/pages/pracovnici.php` | - | - | `Admin:Employee` |
| rodiny | see `families` | see `families` | see `families-update` | `mvc/family/Family.php` | `Admin:Family` |
| dokumenty | `mvc/controller/dokumenty.php` | `template/pages/dokumenty.php` | - | - | `Admin:Document` |
| proposal-records | `mvc/controller/proposal-records.php` | `template/pages/proposal-records.php` | proposal-update | - | `Admin:Proposal` |
| stats | `mvc/controller/stats.php` | `template/pages/stats.php` | - | - | `Admin:Stats` |
| settings | `mvc/controller/settings.php` | `template/pages/settings.php` | - | - | `Admin:Settings` |
| translation | `mvc/controller/translation.php` | `template/pages/translation.php` | - | - | `Admin:Translation` |
| user-management | `mvc/controller/user-management.php` | `template/pages/user-management.php` | user-management-update | - | `Admin:UserManagement` |
| missing-registry | `mvc/controller/missing-registry.php` | `template/pages/missing-registry.php` | - | `mvc/missingregistry/MissingRegistry.php` | `Admin:MissingRegistry` |

Proposal records note: the `Možnosti` button in `template/pages/proposal-records.php` links to `?page=proposal-update&id={proposal.id}`. When migrating the list, keep this as `Admin:Proposal:update`, not `Admin:Family:update`.

## Detail Tabs

Babysitter update subtemplates live in `template/pages/babysitters/`:

| Tab | File | Content |
| --- | --- | --- |
| main | `babysitters/main.php` | Basic identity, nationality, status |
| address | `babysitters/address.php` | Address fields |
| education | `babysitters/education.php` | Education and qualifications |
| workProfile | `babysitters/workProfile.php` | Experience and preferences |
| profil | `babysitters/profil.php` | Profile description |
| shortInfo | `babysitters/shortInfo.php` | Short summary |
| documents | `babysitters/documents.php` | Uploaded documents |
| pdfOutput | `babysitters/pdfOutput.php` | PDF export |

Family update subtemplates live in `template/pages/families/`:

| Tab | File | Content |
| --- | --- | --- |
| main | `families/main.php` | Basic family/client data |
| address | `families/address.php` | Address fields |
| shortInfo | `families/shortInfo.php` | Short summary |
| proposal | `families/proposal.php` | Proposals linked to family |
| documentsContracts | `families/documentsContracts.php` | Contract documents |
| documentsOrders | `families/documentsOrders.php` | Order documents |

Offcanvas panels: `offcanvasFamily`, `offcanvasPartner`, `offcanvasProject`.

## AJAX Endpoints

| Old endpoint | Purpose | New pattern |
| --- | --- | --- |
| `ajax/updateCrud.php` | update a single DB field | Nette form submit or `handleUpdate*()` signal |
| `ajax/hide.php` | soft delete generic record | repository soft delete + `handleDelete()` |
| `ajax/hideTurnus.php` | soft delete turnus | repository soft delete + domain signal |
| `ajax/hideDocument.php` | soft delete document | document repository/service + signal |
| `ajax/hideMissingRegistry.php` | soft delete missing registry row | repository soft delete + signal |
| `ajax/search.php` | global search | existing `SearchControl` pattern |
| `ajax/addRow*.php` | create empty or default row | form submit or `handleCreate*()` |
| `ajax/addRemoveCheckbox*.php` | toggle many-to-many or checkbox value | `handleToggle*()` plus repository method |
| `ajax/createTurnus*.php` | create turnus | domain form or create signal |
| `ajax/createProposalFamily.php` | create family proposal | proposal form/signal |
| `ajax/updatePassword.php` | update password | password form |
| `ajax/uploadFile*.php` | upload document/file | Nette upload form + storage service/provider |

## Key Tables

| Old `Table::$...` | DB table | Domain |
| --- | --- | --- |
| `$babysitters` | `sn_opatrovatelky` | Babysitter |
| `$families` | `sn_families` | Family |
| `$partners` | `sn_partners` | Partner |
| `$agencies` | `sn_agencies` | Agency |
| `$turnus` | `sn_turnus` | Turnus |
| `$todo` | `sn_todo` | Todo |
| `$country` | `sn_country` | Country |
| `$users` | `sany_users` | User |
| `$pages` | `sany_pages` | Page/menu |
| `$documents` | `sn_documents` | Document |
| `$proposal` | `sn_proposal` | Proposal |
| `$status_families` | `sn_status_families` | lookup |
| `$select_language` | `sn_select_language` | lookup |
| `$pohlavie` | `sn_pohlavie` | gender lookup |
| `$babysitter_disease` | `sn_babysitter_disease` | junction |
| `$babysitter_qualification` | `sn_babysitter_qualification` | junction |
| `$babysitter_position_preference` | `sn_babysitter_position_preference` | junction |
