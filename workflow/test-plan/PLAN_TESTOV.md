# Plan testov

## Vychodiskovy stav pred implementaciou

- PHPUnit prechadza: 20 testov, 50 assertions.
- Nette Tester prechadza: 2 testy.
- PHPStan, NEON lint, Latte lint a `node --check www/js/autosaveForm.js` prechadzaju.
- Pokryte su zaklady: test bootstrap, DI container, router, `StringService`, `DateService`, `MonthService`, login smoke, security login attempts, security audit insert a login rate limiter regresia.
- Chyba pokrytie pre plnu test DB schemu, change-log/autosave workflow, vacsinu repository, formularov, presenter/control spravania, JS autosave payloady, dokumenty, video a PDF workflow.

## Stav po implementacii

- PHPUnit prechadza: 74 testov, 634 assertions.
- Nette Tester prechadza: 2 testy.
- PHPStan prechadza s `--memory-limit=512M`.
- NEON lint, Latte lint, `node --check www/js/autosaveForm.js`, `node --check tests/Regression/autosaveFormPayload.test.js` a JS payload harness prechadzaju.
- Implementovane su skupiny Agent 1, Agent 2, Agent 3, Agent 4, Agent 5 a cast Agent 6.
- Doplnene su samostatne test-category priecinky: `tests/Api`, `tests/E2E`, `tests/Mutation`, `tests/Snapshot`, `tests/Performance`, `tests/Smoke`.
- Existujuce presenter smoke testy su organizacne presunute do `tests/Smoke`.
- Agent 5 je dokonceny: supporting repository testy a form factory testy pokryvaju default hodnoty, DTO mapping, validacie a ACL vetvy.
- DB testy treba spustat sekvencne nad jednym `TEST_DATABASE_DSN`; paralelni agenti maju pouzit samostatne `_test` databazy, inak si budu resetovat data.

### Reorganizacia na 1:1 (2026-06-06)

- Repository integration testy maju teraz konvenciu **jedna produkcna trieda = jedna test trieda**
  na zrkadlovej ceste (`app/Model/Repository/XRepository.php` -> `tests/Integration/Repository/XRepositoryTest.php`).
- Zlievane subory rozdelene: `AgencyPartnerRepositoryTest` -> `AgencyRepositoryTest` + `PartnerRepositoryTest`;
  `RegistryCountryUserFileRepositoryTest` -> `MissingRegistryRepositoryTest` + `CountryRepositoryTest` +
  `UserRepositoryTest` + `FileRepositoryTest`; `TodoProposalRepositoryTest` -> `TodoClientRepositoryTest` +
  `FamilyProposalRepositoryTest`.
- Vynimka (zamerne): `ChangeLogRepository` a `SecurityAuditLogRepository` maju dvojicu suborov
  `*Test` (write) + `*ReadTest` (read) — rovnaky nazov repozitara, oddelenie podla concern, plne dohladatelne.

### Doplnene kategorie (2026-06-06)

- **Snapshot** (`tests/Snapshot`): realne testy bezia pod default PHPUnit. PDF Latte template render
  (DB-light cez `BabysitterPdfFixture`), autosave change-log payload tvar a `Admin:Settings` HTML.
  Baseline v `tests/Snapshot/__snapshots__/`, explicitny update cez `UPDATE_SNAPSHOTS=1`.
  Binarnu mpdf vrstvu (nielen markup) kryje `BabysitterPdfBinarySmokeTest` v `tests/Smoke`.
- **Performance** (`tests/Performance`): samostatny config `phpunit.performance.xml`, **nebezi** v default
  PHPUnit. Merania s medianom a env prahmi (`PERF_MAX_MS_*`): babysitter list query, autosave update,
  PDF render. Video metadata test sa skipne, kym nie je fixture (`TEST_VIDEO_FIXTURE`).
- **E2E** (`tests/E2E`): in-process workflow testy (login/logout, wrong-password reject, dealer 403 na
  ChangeLog, autosave->audit->zobrazenie, turnus create/delete + audit) bezia pod default PHPUnit bez
  prehliadaca. Volitelny Playwright scaffold v `tests/E2E/playwright` (login smoke, mimo CI/PHPUnit).
- **API** kryje HTTP/JSON kontrakt zdielaneho `handleAutosavePartial` signalu (same-origin CSRF guard,
  ajax/POST guard, allowed-context allow-list, entity a document ownership, success/error envelope).
  Field-update logiku kryje `AutosaveFieldUpdateServiceTest`; API suite drzi presenter-level zmluvu.
- **Mutation** ostava mimo rozsahu (vyzaduje Infection).
- Zdielane test infra: `SnapshotAssertions`, `PerformanceAssertions`, `PresenterWorkflowTrait`,
  `FakePostRequest`, `BabysitterPdfFixture` v `tests/Support`.
- `phpunit.xml`: pridane suites `Snapshot`, `E2E` a `Api`; `Functional` priecinok zmazany (prazdny po
  presune smoke testov do `tests/Smoke`).

## Spolocny krok pred paralelnou pracou

Agent 1 najprv rozsiri test infra v `tests/Support`:

- Bezpecne vytvorenie plnej `_test` DB schemy z `migrations/database.sql`.
- Doplnenie security/change-log/video migracii.
- Seed helpery pre ciselniky, pouzivatelov a zakladne entity.
- Shared fixture metody pre user, country, babysitter, family, turnus, file a change-log.
- Guard: testy smu zapisovat iba do DB s nazvom konciacim na `_test`.
- Existujuce testy musia stale prejst.

## Paralelne skupiny pre 6 agentov

### Agent 1: test infra a fixture buildery

- Rozsirit `TestDatabase` a podporne PHPUnit triedy.
- Pridat reset/truncate pre domenu, security a audit tabulky.
- Seedovat lookup tabulky deterministicky.
- Vystup: ostatni agenti mozu pisat integration testy bez kopirovania SQL.

### Agent 2: security, audit a change-log

- Testovat `SecurityAuditLogRepository::findLoginRows()`, filtre, pagination, user/event options.
- Testovat `ChangeLogRepository::logChange()`, `findRows()`, filtre `user/date/section/status/entity/q`.
- Overit action label/class a entity label/link resolving pre hlavne entity a dokumenty.

### Agent 3: autosave partial workflow

- Testovat `AutosaveFieldUpdateService` pre text, select, date, float, nullable float, bool, junction checkbox list a document contexts.
- Overit neznami context/field, chybajuce `id`, invalid date, rovnaku hodnotu bez auditu a realnu zmenu s jednym zmenenym stlpcom.

### Agent 4: core domenove repository

- Pokryt `BabysitterRepository`, `FamilyRepository`, `TurnusRepository`.
- Testovat create-empty metody, update z DTO/form objektov, list/filter query, select options, junction ID zoznamy, homepage/month turnus query.

### Agent 5: supporting repository a formulare

- Pokryt `AgencyRepository`, `PartnerRepository`, `TodoClientRepository`, `FamilyProposalRepository`, `MissingRegistryRepository`, `CountryRepository`, `UserRepository`, `FileRepository`.
- Doplnit testy form factory tried: default values, required/rule validacie, DTO mapping, access-denied vetvy.
- Stav: dokoncene.

### Agent 6: presenter/control/JS smoke a workflow regresie

- Rozsirit functional testy pre admin presentery a controls.
- Overit login/logout, route smoke, `ChangeLog` ACL, `Settings -> Evidencia zmien`, create/delete handlery s audit logom.
- Pre `www/js/autosaveForm.js` pridat lahky JS harness alebo minimalne syntax + payload unit testy pre partial/legacy fallback.

## Doplnene typy testov

| Typ | Priecinok | Stav | Runner |
| --- | --- | --- | --- |
| Smoke | `tests/Smoke` | Hotovo. Boot/render kontroly, presenter smoke testy a mpdf binarny smoke (`BabysitterPdfBinarySmokeTest`). | PHPUnit default |
| API | `tests/Api` | Hotovo. HTTP/JSON kontrakt `handleAutosavePartial`: CSRF/ajax guard, allowed-context, entity+document ownership, success/error envelope. | PHPUnit default |
| Snapshot | `tests/Snapshot` | Hotovo. PDF template render, autosave payload tvar, `Admin:Settings` HTML; baseline + `UPDATE_SNAPSHOTS=1`. | PHPUnit default |
| E2E | `tests/E2E` | Hotovo (in-process). Login/logout, ACL, autosave audit, turnus create/delete. Browser vrstva = volitelny Playwright scaffold. | PHPUnit default + opcny Playwright |
| Mutation | `tests/Mutation` | Mimo rozsahu. Vyziada si Infection a cieleny scope. | Samostatny runner |
| Performance | `tests/Performance` | Hotovo. Query, autosave, PDF render s medianom a env prahmi; video metadata sa skipne bez fixture. | `phpunit.performance.xml` |

## Verejne rozhrania a konvencie

- PHPUnit je hlavny test runner.
- Nette Tester ostava ako kompatibilny smoke balik.
- Nove helpery patria pod `tests/Support`, nie do `app`.
- Default PHPUnit suites: `Unit`, `Integration`, `Smoke`, `Regression`, `Snapshot`, `E2E`.
- `Performance` ma vlastny config (`phpunit.performance.xml`) a nespusta sa v default PHPUnit prikaze.
- `Mutation` a browser `Playwright` E2E maju vlastne runnery a nie su sucastou default behu.
- Shared fixture API ma ostat male: `ensureSchema`, `reset`, `insert`, `createUser`, `createCountry`, `createBabysitter`, `createFamily`, `createTurnus`, `createFile`, `createChangeLog`.

## Akceptacne kriteria

- Po kazdej skupine musia prejst `vendor/bin/phpunit`, `vendor/bin/tester tests/Tester -s`, PHPStan, NEON lint a Latte lint.
- Testy nesmu zavisiet od produkcnych dat ani od existujucich suborov v `private/`.
- DB testy musia mat vlastny seed a deterministicke ocakavania.
- Xdebug startup warning sa moze riesit samostatne ako infra cleanup, neblokuje tento plan.

## Predpoklady

- Priorita je workflow `autosave-change-audit`, potom najrizikovejsie domenove repository a az potom plosne UI smoke testy.
- Ciel nie je 100 percent coverage, ale stabilny regresny balik pre nove zmeny a kriticke business workflow.
