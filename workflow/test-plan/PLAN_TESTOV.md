# Plan testov

## Vychodiskovy stav pred implementaciou

- PHPUnit prechadza: 20 testov, 50 assertions.
- Nette Tester prechadza: 2 testy.
- PHPStan, NEON lint, Latte lint a `node --check www/js/autosaveForm.js` prechadzaju.
- Pokryte su zaklady: test bootstrap, DI container, router, `StringService`, `DateService`, `MonthService`, login smoke, security login attempts, security audit insert a login rate limiter regresia.
- Chyba pokrytie pre plnu test DB schemu, change-log/autosave workflow, vacsinu repository, formularov, presenter/control spravania, JS autosave payloady, dokumenty, video a PDF workflow.

## Stav po implementacii

- PHPUnit prechadza: 60 testov, 436 assertions.
- Nette Tester prechadza: 2 testy.
- PHPStan prechadza s `--memory-limit=512M`.
- NEON lint, Latte lint, `node --check www/js/autosaveForm.js`, `node --check tests/Regression/autosaveFormPayload.test.js` a JS payload harness prechadzaju.
- Implementovane su skupiny Agent 1, Agent 2, Agent 3, Agent 4 a cast Agent 6.
- Agent 5 ostava najvacsi dalsi backlog: supporting repository a form factory testy.
- DB testy treba spustat sekvencne nad jednym `TEST_DATABASE_DSN`; paralelni agenti maju pouzit samostatne `_test` databazy, inak si budu resetovat data.

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

### Agent 6: presenter/control/JS smoke a workflow regresie

- Rozsirit functional testy pre admin presentery a controls.
- Overit login/logout, route smoke, `ChangeLog` ACL, `Settings -> Evidencia zmien`, create/delete handlery s audit logom.
- Pre `www/js/autosaveForm.js` pridat lahky JS harness alebo minimalne syntax + payload unit testy pre partial/legacy fallback.

## Verejne rozhrania a konvencie

- PHPUnit je hlavny test runner.
- Nette Tester ostava ako kompatibilny smoke balik.
- Nove helpery patria pod `tests/Support`, nie do `app`.
- Nove testy patria do existujucich suites: `Unit`, `Integration`, `Functional`, `Regression`.
- Shared fixture API ma ostat male: `ensureSchema`, `reset`, `insert`, `createUser`, `createCountry`, `createBabysitter`, `createFamily`, `createTurnus`, `createFile`, `createChangeLog`.

## Akceptacne kriteria

- Po kazdej skupine musia prejst `vendor/bin/phpunit`, `vendor/bin/tester tests/Tester -s`, PHPStan, NEON lint a Latte lint.
- Testy nesmu zavisiet od produkcnych dat ani od existujucich suborov v `private/`.
- DB testy musia mat vlastny seed a deterministicke ocakavania.
- Xdebug startup warning sa moze riesit samostatne ako infra cleanup, neblokuje tento plan.

## Predpoklady

- Priorita je workflow `autosave-change-audit`, potom najrizikovejsie domenove repository a az potom plosne UI smoke testy.
- Ciel nie je 100 percent coverage, ale stabilny regresny balik pre nove zmeny a kriticke business workflow.
