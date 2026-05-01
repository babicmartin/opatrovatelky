# Old CRM Page Map

Base path: `C:\wamp64\www\monika\opatrovatelky\old\`

## Pages

| Old Page | Controller | Template | Update Page | Model Class | New Presenter |
|----------|-----------|----------|-------------|-------------|---------------|
| homepage | controller/homepage.php | pages/homepage.php | - | - | Admin:Home |
| opatrovatelky | controller/opatrovatelky.php | pages/opatrovatelky.php | opatrovatelky-update | mvc/babysitter/Babysitter.php | Admin:Babysitter |
| families | controller/families.php | pages/families.php | families-update | mvc/family/Family.php | Admin:Family |
| partneri | controller/partneri.php | pages/partneri.php | partneri-update | - | Admin:Partner |
| turnus | controller/turnus.php | pages/turnus.php | turnus-update | mvc/turnus/Turnus.php | Admin:Rotation |
| turnus-records-month | controller/turnus-records-month.php | pages/turnus-records-month.php | - | - | Admin:Rotation:month |
| turnus-select-month | controller/turnus-select-month.php | pages/turnus-select-month.php | - | - | Admin:Rotation:selectMonth |
| projekty | controller/projekty.php | pages/projekty.php | - | - | Admin:Project |
| opatrovanie | controller/opatrovanie.php | pages/opatrovanie.php | opatrovanie-update | - | Admin:Care |
| agencies | controller/agencies.php | pages/agencies.php | agencies-update | - | Admin:Agency |
| country | controller/country.php | pages/country.php | country-update | - | Admin:Country |
| todo | controller/todo.php | pages/todo.php | todo-update | mvc/todo/Todo.php | Admin:Todo |
| pracovnici | controller/pracovnici.php | pages/pracovnici.php | - | - | Admin:Employee |
| dokumenty | controller/dokumenty.php | pages/dokumenty.php | - | - | Admin:Document |
| proposal-records | controller/proposal-records.php | pages/proposal-records.php | proposal-update | - | Admin:Proposal |
| stats | controller/stats.php | pages/stats.php | - | - | Admin:Stats |
| settings | controller/settings.php | pages/settings.php | - | - | Admin:Settings |
| translation | controller/translation.php | pages/translation.php | - | - | Admin:Translation |
| user-management | controller/user-management.php | pages/user-management.php | user-management-update | - | Admin:UserManagement |
| missing-registry | controller/missing-registry.php | pages/missing-registry.php | - | mvc/missingregistry/MissingRegistry.php | Admin:MissingRegistry |

## Babysitter Detail Tabs (opatrovatelky-update)

Sub-templates in `template/pages/babysitters/`:

| Tab | File | Content |
|-----|------|---------|
| main | babysitters/main.php | Basic info (name, birth, nationality, status) |
| address | babysitters/address.php | Address fields |
| education | babysitters/education.php | Education, qualifications |
| workProfile | babysitters/workProfile.php | Work experience, preferences |
| profil | babysitters/profil.php | Profile description |
| shortInfo | babysitters/shortInfo.php | Short summary |
| documents | babysitters/documents.php | Uploaded documents |
| pdfOutput | babysitters/pdfOutput.php | PDF export |

## Family Detail Tabs (families-update)

Sub-templates in `template/pages/families/`:

| Tab | File | Content |
|-----|------|---------|
| main | families/main.php | Basic info (name, country, contact) |
| address | families/address.php | Address fields |
| shortInfo | families/shortInfo.php | Short summary |
| proposal | families/proposal.php | Proposals linked to family |
| documentsContracts | families/documentsContracts.php | Contract documents |
| documentsOrders | families/documentsOrders.php | Order documents |

## AJAX Endpoints (ajax/)

| File | Purpose | New pattern |
|------|---------|-------------|
| updateCrud.php | Update single DB column | Nette form or handleUpdate signal |
| hide.php | Soft delete (deleted=1) | handleDelete signal |
| hideTurnus.php | Soft delete turnus | handleDelete signal |
| hideDocument.php | Soft delete document | handleDelete signal |
| hideMissingRegistry.php | Soft delete missing | handleDelete signal |
| search.php | Global search | SearchControl (already exists) |
| addRow.php | Insert empty row | handleCreate signal or form |
| addRowBabysitter.php | Insert babysitter | handleCreate signal |
| addRowFamily.php | Insert family | handleCreate signal |
| addRemoveCheckbox.php | Toggle checkbox value | handleToggle signal |
| addRemoveCheckboxQualificationPreference.php | Toggle qualification | handleToggle signal |
| createTurnus.php | Create turnus record | handleCreateRotation signal |
| createTurnusFamily.php | Create turnus for family | handleCreateRotation signal |
| createProposalFamily.php | Create proposal | handleCreateProposal signal |
| updatePassword.php | Change password | PasswordForm |
| uploadFile.php | Upload document | Nette upload form |
| uploadFileJQuery.php | Upload (jQuery) | Nette upload form |

## Key DB Tables (Table::$xxx)

| Static property | Table name | Domain |
|-----------------|------------|--------|
| $babysitters | sn_opatrovatelky | Babysitter |
| $families | sn_families | Family |
| $partners | sn_partners | Partner |
| $agencies | sn_agencies | Agency |
| $turnus | sn_turnus | Rotation |
| $todo | sn_todo | Todo |
| $country | sn_country | Country |
| $users | sany_users | User |
| $pages | sany_pages | Page |
| $documents | sn_documents | Document |
| $proposal | sn_proposal | Proposal |
| $status_families | sn_status_families | (lookup) |
| $select_language | sn_select_language | (lookup) |
| $pohlavie | sn_pohlavie | (lookup: gender) |
| $babysitter_disease | sn_babysitter_disease | (junction) |
| $babysitter_qualification | sn_babysitter_qualification | (junction) |
| $babysitter_position_preference | sn_babysitter_position_preference | (junction) |
