# Old template URL redirect report

Datum: 2026-05-12

Rozsah kontroly: `C:\wamp64\www\monika\opatrovatelky\old\template`

## Zhrnutie

Legacy admin pouzival hlavne root query URL vo formate `/?page=...`, pripadne `/index.php?page=...`. Novy web pouziva Nette routy ako `/family`, `/babysitter/update/{id}` alebo `/turnus/month`.

Redirecty su pripravene pre stare query URL a patria do `www/.htaccess` pred front controller. Interne assety, CDN URL, `mailto:`, kotvy a AJAX endpointy sa nepresmeruvaju.

## Redirectovane interne URL

| Stara URL / pattern | Zdroj v `old/template` | Nova URL |
|---|---|---|
| `/?page=homepage` alebo root logo link | `header.php`, `pages/homepage.php` | `/` |
| `/?page=logout` | `header.php` | `/logout/` |
| `/?page=stats` | `pages/homepage.php` | `/stats` |
| `/?page=settings` | `pages/settings.php`, page file | `/settings` |
| `/?page=missing-registry` | `header.php`, `pages/settings.php` | `/missing-registry` |
| `/?page=country` | `pages/settings.php`, page file | `/country` |
| `/?page=country-update&id={id}` | `pages/country.php` | `/country/update/{id}` |
| `/?page=translation` | `pages/settings.php` | `/translation` |
| `/?page=user-management` | `pages/settings.php`, page file | `/user-management` |
| `/?page=user-management-update&id={id}` | `pages/user-management.php` | `/user-management/update/{id}` |
| `/?page=families` | `pages/families.php`, `pages/families/*`, turnus/todo/proposal links | `/family` |
| `/?page=families&country={id}` | family tables | `/family?country={id}` |
| `/?page=families&partner={id}` | family tables | `/family?partner={id}` |
| `/?page=families&user={id}` | family tables | `/family?user={id}` |
| `/?page=families&user={id}&status={id}` | `pages/families.php` manager buttons | `/family?user={id}&status={id}` |
| `/?page=families&status={id}` | family tables | `/family?status={id}` |
| `/?page=families-update&id={id}` | family, turnus, todo and proposal templates | `/family/update/{id}` |
| `/?page=families-update&id={id}&address=1` | `pages/families-update.php`, `pages/proposal-update.php` | `/family/update/{id}?tab=info` |
| `/?page=families-update&id={id}&proposal=1` | `pages/families-update.php`, `pages/proposal-update.php` | `/family/update/{id}?tab=proposals` |
| `/?page=families-update&id={id}&contract=1` | `pages/families-update.php`, `pages/proposal-update.php` | `/family/update/{id}?tab=contracts` |
| `/?page=families-update&id={id}&order=1` | `pages/families-update.php`, `pages/proposal-update.php` | `/family/update/{id}?tab=orders` |
| `/?page=projekty` | `pages/projekty.php`, `pages/projects/*` | `/project` |
| `/?page=proejekty` | typo in `pages/projects/tableHomepage.php` | `/project` |
| `/?page=projekty&country={id}` | project tables | `/project?country={id}` |
| `/?page=projekty&partner={id}` | project tables | `/project?partner={id}` |
| `/?page=projekty&user={id}` | project tables | `/project?user={id}` |
| `/?page=projekty&user={id}&status={id}` | `pages/projekty.php` manager buttons | `/project?user={id}&status={id}` |
| `/?page=projekty&status={id}` and `/?page=proejekty&status={id}` | project tables | `/project?status={id}` |
| `/?page=opatrovatelky` | babysitter tables, page file | `/babysitter` |
| `/?page=opatrovatelky&gender={id}` | babysitter table | `/babysitter?gender={id}` |
| `/?page=opatrovatelky&country={id}` | babysitter table | `/babysitter?country={id}` |
| `/?page=opatrovatelky&agency={id}` | babysitter table | `/babysitter?agency={id}` |
| `/?page=opatrovatelky&status={id}` | babysitter table | `/babysitter?status={id}` |
| `/?page=opatrovatelky-update&id={id}` | babysitter, worker, turnus, todo and proposal templates | `/babysitter/update/{id}` |
| `/?page=opatrovatelky-update&id={id}&address=1` | `pages/opatrovatelky-update.php` | `/babysitter/update/{id}?tab=info` |
| `/?page=opatrovatelky-update&id={id}&education=1` | `pages/opatrovatelky-update.php` | `/babysitter/update/{id}?tab=education` |
| `/?page=opatrovatelky-update&id={id}&profil=1` | `pages/opatrovatelky-update.php` | `/babysitter/update/{id}?tab=profil` |
| `/?page=opatrovatelky-update&id={id}&work-profile=1` | `pages/opatrovatelky-update.php` | `/babysitter/update/{id}?tab=work-profile` |
| `/?page=opatrovatelky-update&id={id}&documents=1` | `pages/opatrovatelky-update.php` | `/babysitter/update/{id}?tab=documents` |
| `/?page=opatrovatelky-update&id={id}&pdf=1` | `pages/opatrovatelky-update.php` | `/babysitter/update/{id}?tab=pdf` |
| `/?page=pracovnici` | `pages/pracovnici.php`, `pages/pracovnici/table.php` | `/worker` |
| `/?page=pracovnici&gender={id}` | worker table | `/worker?gender={id}` |
| `/?page=pracovnici&country={id}` | worker table | `/worker?country={id}` |
| `/?page=pracovnici&agency={id}` | worker table | `/worker?agency={id}` |
| `/?page=pracovnici&status={id}` | worker table | `/worker?status={id}` |
| `/?page=partneri` | partner tables, page file | `/partner` |
| `/?page=partneri&country={id}` | partner table | `/partner?country={id}` |
| `/?page=partneri&status={id}` | partner table | `/partner?status={id}` |
| `/?page=partneri-update&id={id}` | partner, proposal and offcanvas templates | `/partner/update/{id}` |
| `/?page=partneri-update={id}` | typo in `pages/partners/offcanvasActiveFamily.php` | `/partner/update/{id}` |
| `/?page=opatrovanie` | page file / legacy controller maps partner table | `/partner` |
| `/?page=opatrovanie-update&id={id}` | page file / legacy controller maps partner table | `/partner/update/{id}` |
| `/?page=agencies` | agency table, page file | `/agency` |
| `/?page=agencies&country={id}` | agency table | `/agency?country={id}` |
| `/?page=agencies&status={id}` | agency table | `/agency?status={id}` |
| `/?page=agencies-update&id={id}` | agency table | `/agency/update/{id}` |
| `/?page=turnus` | turnus templates | `/turnus` |
| `/?page=turnus&finish=1` | `pages/turnus.php` | `/turnus?finish=1` |
| `/?page=turnus&status={id}` | turnus tables | `/turnus?status={id}` |
| `/?page=turnus&order={id}&finish={id}&status={id}` | `pages/turnus/table.php` | `/turnus?order={id}&finish={id}&status={id}` |
| `/?page=turnus-update&id={id}` | turnus tables and unpaid invoices | `/turnus/update/{id}` |
| `/?page=turnus-select-month` | `header.php` | `/turnus/select-month` |
| `/?page=turnus-records-month&year={year}&month={month}` | `pages/turnus-select-month.php` | `/turnus/month?year={year}&month={month}` |
| `/?page=turnus-records-month&year{year}&month={month}` | typo in `pages/turnus-select-month.php` | `/turnus/month?year={year}&month={month}` |
| `/?page=proposal-records` | `header.php`, proposal templates | `/proposal` |
| `/?page=proposal` | menu destination equivalent | `/proposal` |
| `/?page=proposal-update&id={id}` | proposal templates | `/proposal/update/{id}` |
| `/?page=todo` | todo templates | `/todo` |
| `/?page=todo&status={id}` | todo tables | `/todo?status={id}` |
| `/?page=todo-update&id={id}` | todo tables | `/todo/update/{id}` |
| `/?page=dokumenty` | empty page file | `/` |
| `/?page=offcanvasFamily` | included partial | `/family` |
| `/?page=offcanvasPartner` | included partial | `/partner` |
| `/?page=offcanvasProject` | included partial | `/project` |

## Najdene URL bez redirectu

- Assety z legacy layoutu: `web/assets/...`, `web/img/...`, `web/js/...`.
- Externe zdroje v hlavicke/paticke: `https://cdn.jsdelivr.net/...`, `https://cdnjs.cloudflare.com/...`, `https://code.jquery.com/...`, `https://unpkg.com/...`, `//code.jquery.com/...`.
- `mailto:` odkazy v tabulkach partnerov a agentur.
- Lokalne kotvy a JS-only odkazy: `#top`, `javascript:void(0)`.
- AJAX endpointy pouzivane starym JS: `ajax/updateCrud.php`, `ajax/search.php`, `ajax/uploadFile.php`, `ajax/createTurnus.php`, a podobne. Tieto nie su verejne HTML stranky novej aplikacie.

## Poznamky

- Pravidla v `.htaccess` predpokladaju, ze stare a nove zaznamy pouzivaju rovnake ciselne ID.
- Pri strankach bez novej priamej nahrady (`dokumenty`, interne offcanvas partialy) je pouzity najblizsi bezpecny ciel.
- Redirecty su 301, aby sa stare odkazy po nasadeni ostrej verzie nestratili.
