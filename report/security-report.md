# Security report: login a autorizacia

Dátum kontroly: 2026-05-08

Rozsah kontroly: login flow, autentifikátor, ACL/autorizácia admin časti a databázové predpoklady pre používateľské účty.

## Zhrnutie

Kontrola odhalila dve otvorené bezpečnostné riziká s priamym dopadom na prístup do administrácie:

1. Administrácia nemá globálne vynútené prihlásenie. V kóde je to označené ako development-only bypass a nesmie sa dostať do produkcie.
2. Rola `guest` má v ACL povolený prístup ku všetkým zdrojom. V kóde je to označené ako development-only bypass a nesmie sa dostať do produkcie.

Čo je v poriadku:

- Heslá sa hašujú cez `Nette\Security\Passwords` (Argon2ID) s automatickým rehashom legacy SHA-256 (`AdminAuthenticator.php:40` až `AdminAuthenticator.php:59`).
- SQL dotazy idú cez Nette Database Selection API s parameterizovanými `where()` klauzulami — žiadna konkatenácia.
- Formuláre vytvorené cez `BaseFormFactory` používajú `addProtection()` (CSRF token), čo platí aj pre login formulár.
- Hierarchický ACL má korektnú dedičnosť rolí DEALER_JUNIOR < DEALER < CEO < ADMIN.
- Session cookie má nastavené `cookieSecure: auto`, `cookieSamesite: Lax`, `cookieHttponly: true` v `config/application.neon`.
- Bezpečnostné HTTP hlavičky (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy) sú aktívne v `www/.htaccess`. HSTS a CSP sú zakomentované — zapnúť po nasadení produkčného HTTPS / inventarizácii zdrojov.
- Session ID sa explicitne regeneruje po úspešnom logine v `LoginFormFactory::onSuccess()` aj po logoute v `LoginPresenter::actionLogout()`.

Chýbajúci rate limiting a audit loginu sú uvedené v pláne zabezpečenia ako prioritné doplnenie. SQL DDL sú pripravené v `report/sql/`.

## 1. Administrácia nemá globálne vynútené prihlásenie

Závažnosť: kritická pri produkčnom nasadení, ak development-only bypass ostane zapnutý

Dôkaz v kóde:

- `app/UI/Admin/AdminPresenter.php:41` obsahuje `TODO SECURITY` komentár, že ide o development-only bypass.
- `app/UI/Admin/AdminPresenter.php:42` až `app/UI/Admin/AdminPresenter.php:43` majú zakomentovanú kontrolu `isLoggedIn()` a redirect na `@login`.
- `app/UI/Admin/AdminPresenter.php:49` následne kontroluje iba ACL, nie samotné prihlásenie.

Dopad:

Neprihlásený používateľ sa môže dostať na admin routy, ak ho nezastaví konkrétny presenter alebo komponent. Ochrana je nekonzistentná, pretože napríklad `UserManagementPresenter` si login kontrolu rieši sám, ale základný `AdminPresenter` ju nevynucuje pre celú administráciu.

Odporúčané riešenie:

- V `AdminPresenter::startup()` obnoviť globálnu kontrolu prihlásenia pre všetky admin presentery okrem `Login:Login`.
- Neprihláseného používateľa presmerovať na `@login` a cez `storeRequest()` zachovať pôvodnú požiadavku.
- Po prihlásení obnoviť pôvodnú požiadavku iba ak smeruje do povolenej admin časti.

Príklad cieľového správania:

```php
if (!$this->getUser()->isLoggedIn() && $this->getName() !== 'Login:Login') {
	$this->storeRequest();
	$this->redirect('@login');
}
```

## 2. Rola `guest` má povolený prístup ku všetkým zdrojom

Závažnosť: kritická pri produkčnom nasadení, ak development-only bypass ostane zapnutý

Dôkaz v kóde:

- `app/Model/Security/Authorizator/AuthorizatorFactory.php:21` vytvára rolu `guest`.
- `app/Model/Security/Authorizator/AuthorizatorFactory.php:28` obsahuje `TODO SECURITY` komentár, že ide o development-only bypass.
- `app/Model/Security/Authorizator/AuthorizatorFactory.php:29` volá `$acl->allow('guest')`.
- Test správania `Nette\Security\Permission` potvrdil, že `allow('guest')` bez zdroja povolí roli `guest` prístup ku konkrétnemu zdroju.

Dopad:

Aj keď presenter volá `$this->getUser()->isAllowed($resource, $privilege)`, neprihlásený používateľ môže prejsť autorizáciou ako `guest`. Toto obchádza význam ACL a ruší ochranu admin zdrojov.

Odporúčané riešenie:

- Odstrániť globálne `$acl->allow('guest')`.
- Hosťovi nepovoľovať admin zdroje.
- Ak aplikácia potrebuje verejné routy, vytvoriť explicitný verejný resource, napríklad `public.login`, a povoliť len ten.
- Dopísať test ACL: `guest` nesmie mať povolené `Resource::USER_MANAGEMENT`, `Resource::FAMILY`, `Resource::BABYSITTER`, `Resource::TURNUS` ani ostatné admin zdroje.

Príklad cieľového správania:

```php
$acl->addRole('guest');
// Bez globálneho allow pre guest.
```

## Doplnkové riziko: chýba rate limiting a audit loginu

`app/UI/Login/Control/Login/LoginFormFactory.php:45` priamo volá `$this->user->login(...)` a pri chybe iba zaloguje warning. V kóde nie je viditeľná per-IP ani per-email ochrana proti opakovaným pokusom o heslo.

Toto riziko odporúčam riešiť spolu s vyššie uvedenými opravami. SQL návrhy sú pripravené v:

- `report/sql/001_create_security_login_attempts.sql`
- `report/sql/002_create_security_audit_log.sql`

## Plan na lepsie zabezpecenie systemu

Priorita 1: uzavretie prístupu do administrácie

- Zapnúť globálne vynútenie loginu v `AdminPresenter`.
- Odstrániť globálne povolenie `guest`.
- Pridať regresné testy pre neprihlásený prístup a ACL rolu `guest`.

Priorita 2: spevnenie loginu

- Zjednotiť login chyby smerom k používateľovi, aby systém neprezrádzal, či email existuje alebo je heslo zlé.

Priorita 3: ochrana proti hádaniu hesiel

- Zaviesť `security_login_attempts`.
- Po 5 neúspešných pokusoch za 15 minút zablokovať ďalšie pokusy pre kombináciu email + IP.
- Pri väčšom množstve pokusov z jednej IP pridať IP-level throttling.
- Pri úspešnom prihlásení nemažte históriu, iba ju používajte cez časové okno.

Priorita 4: audit a prevádzkový monitoring

- Zaviesť `security_audit_log`.
- Logovať udalosti `login_success`, `login_failed`, `login_blocked`, `logout`, `password_changed`.
- Monitorovať nárast `login_failed` a `login_blocked`.
- Pravidelne kontrolovať účty s admin/CEO právami.

Priorita 5: produkčné HTTPS hardening (otvorené)

- Po nasadení HTTPS na produkciu odkomentovať HSTS a HTTPS rewrite v `www/.htaccess`.
- Postupne zaviesť Content Security Policy pre admin layout, najmä pre externé skripty z CDN — najprv v report-only móde, potom enforce.

## Akceptacne scenare po implementacii

- Neprihlásený používateľ otvorí `/family/`, `/babysitter/`, `/turnus/` alebo `/user-management/` a je presmerovaný na `/login/`.
- Rola `guest` má `isAllowed()` pre všetky admin zdroje nastavené na `false`.
- Po opakovaných neúspešných pokusoch sa login dočasne zablokuje.
- Úspešné, neúspešné a zablokované loginy vzniknú v audit logu.
