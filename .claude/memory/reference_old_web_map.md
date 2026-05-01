---
name: Old Web Structure Map
description: Complete map of the old CRM at C:\wamp64\www\monika\opatrovatelky\old - pages, controllers, templates for migration reference
type: reference
---

## Old Web Location
`C:\wamp64\www\monika\opatrovatelky\old`

## Architecture
Simple MVC with `?page=` routing. Entry: `index.php` → Router → Controller → Template.
Menu from DB table `sany_pages` (columns: active, parent, permission, position, in_menu, url, name).
Auth via session, `crypt($password, '$5$')` SHA-256 hash.

## Pages / Modules (controller → template)

| Stará stránka | Popis | Nový Presenter (navrhovaný) |
|---|---|---|
| homepage | Dashboard, prehľad projektov + turnusov + rodín | Admin:Home |
| opatrovatelky | Zoznam opatrovateliek (babysitters) | Admin:Babysitter:List |
| opatrovatelky-update | Detail/edit opatrovateľky | Admin:Babysitter:Edit |
| families | Zoznam rodín | Admin:Family:List |
| families-update | Detail/edit rodiny | Admin:Family:Edit |
| partneri | Zoznam partnerov | Admin:Partner:List |
| partneri-update | Detail/edit partnera | Admin:Partner:Edit |
| turnus | Zoznam turnusov | Admin:Turnus:List |
| turnus-update | Detail/edit turnusu | Admin:Turnus:Edit |
| turnus-records-month | Turnusy podľa mesiaca | Admin:Turnus:Month |
| turnus-select-month | Výber mesiaca pre turnus | Admin:Turnus:SelectMonth |
| projekty | Zoznam projektov | Admin:Project:List |
| opatrovanie | Opatrovanie prehľad | Admin:Care:List |
| opatrovanie-update | Detail/edit opatrovania | Admin:Care:Edit |
| agencies | Agentúry | Admin:Agency:List |
| agencies-update | Detail/edit agentúry | Admin:Agency:Edit |
| country | Krajiny | Admin:Country:List |
| country-update | Detail/edit krajiny | Admin:Country:Edit |
| todo | Úlohy (TODO) | Admin:Todo:List |
| todo-update | Detail/edit úlohy | Admin:Todo:Edit |
| pracovnici | Pracovníci (zamestnanci) | Admin:Employee:List |
| rodiny | Rodiny (alternatívny view?) | Admin:Family:List |
| dokumenty | Dokumenty | Admin:Document:List |
| proposal-records | Záznamy návrhov | Admin:Proposal:List |
| proposal-update | Detail/edit návrhu | Admin:Proposal:Edit |
| stats | Štatistiky | Admin:Stats:Default |
| settings | Nastavenia | Admin:Settings:Default |
| translation | Preklady | Admin:Translation:Default |
| user-management | Správa používateľov | Admin:UserManagement:List |
| user-management-update | Detail/edit používateľa | Admin:UserManagement:Edit |
| missing-registry | Chýbajúce záznamy | Admin:MissingRegistry:Default |

## Offcanvas (slide-in panels)
- offcanvasFamily - detail rodiny v paneli
- offcanvasPartner - detail partnera v paneli  
- offcanvasProject - detail projektu v paneli

## Babysitter Detail Tabs (opatrovatelky-update)
- main - základné údaje
- address - adresa
- education - vzdelanie
- workProfile - pracovný profil
- profil - profil
- shortInfo - krátke info
- documents - dokumenty
- pdfOutput - PDF export

## Family Detail Tabs (families-update)
- main - základné údaje
- address - adresa
- shortInfo - krátke info
- proposal - návrh
- documentsContracts - zmluvy
- documentsOrders - objednávky

## AJAX Endpoints (ajax/)
- addRemoveCheckbox - toggle checkbox hodnôt
- addRemoveCheckboxQualificationPreference - kvalifikácie
- addRow / addRowBabysitter / addRowFamily - pridanie záznamov
- createProposalFamily - vytvorenie návrhu pre rodinu
- createTurnus / createTurnusFamily - vytvorenie turnusu
- hide / hideDocument / hideMissingRegistry / hideTurnus - soft delete
- search - vyhľadávanie
- updateCrud - CRUD operácie
- updatePassword - zmena hesla
- uploadFile / uploadFileJQuery - upload súborov

## MVC Classes (mvc/)
- App, WebApp, WebAppAjax - application core
- Babysitter - babysitter business logic
- Family - family business logic
- Turnus - turnus business logic
- Todo - todo business logic
- MissingRegistry - missing registry logic
- Database - DB connection (Nette Database Explorer)
- Crud - generic CRUD operations
- Data - data loading
- Documents - document management
- Images - image handling
- Menu - menu from sany_pages table
- Page - page rendering
- Render / RenderPdf - output rendering + mPDF
- Router - simple ?page= routing
- Search - search functionality
- Table - table rendering
- User - user/auth management
- Functions - utility functions

## Assets (web/)
- web/assets/ - CSS, fonts, plugins (Bootstrap-based admin template)
- web/js/ - custom JavaScript
- web/img/ - images
- web/documents/ - uploaded documents
- web/export/ - exported files

## Key DB Tables (from TableMap + Menu)
- sany_users - používatelia
- sany_pages - menu/stránky (url, name, permission, position, active, parent, in_menu)
- (all other tables as defined in app/Model/Table/*TableMap.php)
