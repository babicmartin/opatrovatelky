# Turnus update - follow-up report k autosave

## Kontext

Detail turnusu bol migrovany z legacy stranky `old/template/pages/turnus-update.php`. Legacy verzia ukladala kazde pole samostatne cez AJAX endpointy typu `updateCrud.php`, kde browser posielal `id`, `table`, `column` a `val`. Nova Nette verzia pouziva bezpecnejsi pristup: konkretne Nette formulare s CSRF ochranou a server-side allowlistom poli.

Aktualna implementacia vsak stale posiela pri autosave cely formular. Toto je bezpecnejsie ako legacy genericky `table/column` update, ale funkcne to nie je idealne pre detail turnusu s mnozstvom poli.

Poznamka po aktualnej oprave: float polia detailu turnusu uz maju zakladnu server-side normalizaciu pre desatinne cisla s bodkou aj ciarkou. Tento report stale riesi sirsi autosave model, nie samotne parsovanie floatov.

## Hlavne rizika

### 1. Prepisanie inych poli

Pri zmene jedneho pola sa odosle cely formular. Ak iny pouzivatel medzi nacitanim stranky a autosave ulozi iny stlpec, neskorsi autosave moze jeho hodnotu prepisat starsou hodnotou z otvoreneho formulara.

Riziko je najvyssie pri detailoch ako turnus, kde viac ludi moze upravovat statusy, fakturacne cisla, poznamky alebo datumy.

### 2. Strata rychlych zmien

`www/js/autosaveForm.js` ma guard `form.dataset.autosaveSaving === '1'`. Ak pouzivatel zmeni dalsie pole pocas prebiehajuceho requestu, dalsia zmena sa neodosle. UI potom moze vyzerat upravene, ale databaza ostane so starsou hodnotou.

### 3. Nevalidny datum sa moze zmenit na `NULL`

Datumy sa parsujú cez `DateService::tryCreateFromUserInput()`. Ak pouzivatel zada nevalidny format, metoda vrati `null`. Pri celom-form-submit autosave to moze ulozit prazdnu hodnotu namiesto toho, aby sa konkretna nevalidna zmena odmietla.

### 4. Slabsia spatna vazba pri serverovej validacii

Skript dnes rozlisuje hlavne HTTP uspech/neuspech. Pri field-level validacii by mal server vratit presny stav konkretneho pola: ulozene, odmietnute, konflikt alebo nevalidny format.

### 5. Konflikt oddelenych formularov

Ak jedna cast stranky uklada samostatny formular a hlavny formular stale nesie stare hodnoty tych istych stlpcov, hlavny formular ich moze neskor vratit spat. Preto je pri oddelovani formularov dolezite, aby sa jeden DB stlpec nachadzal len v jednom autosave formulári.

## Odporucane riesenie

Najbezpecnejsi dalsi krok je field-scoped autosave, nie navrat ku generickemu legacy AJAX endpointu.

Navrh:

1. Kazde autosave pole posiela iba nazov konkretneho povoleneho pola a jeho hodnotu.
2. Server nepouziva klientsky `table` ani `column` parameter.
3. Presenter alebo specializovana form factory mapuje verejny nazov pola na presny DB stlpec cez server-side allowlist.
4. Kazdy request obsahuje CSRF token, ID turnusu a jednu hodnotu.
5. Server validuje iba dane pole a vrati JSON odpoved:
   - `success: true` pri ulozeni,
   - `success: false` a `message` pri validacii,
   - `conflict: true` pri zisteni zmeny od ineho pouzivatela.
6. Klient ma per-control queue: ak sa pole zmeni pocas ulozenia, po dobehnuti requestu sa odosle najnovsia hodnota.

## Bezpecnejsi flow pre klienta

Pre kazdy input/select/textarea:

1. Na `change` alebo `focusout` ulozit novu hodnotu ako `pendingValue`.
2. Ak pole prave neuklada, odoslat request.
3. Ak pocas requestu pride dalsia zmena, neignorovat ju. Oznacit ju ako pending.
4. Po uspesnom requeste porovnat ulozenu hodnotu s aktualnou hodnotou v poli.
5. Ak sa lisia, okamzite odoslat dalsi request s aktualnou hodnotou.
6. UI border farbit iba pre konkretne pole, nie pre cely formular.

## Server-side navrh pre turnus

Vytvorit specializovany endpoint/signal napriklad:

`handleUpdateField(int $id, string $field): void`

Serverova mapa:

```php
private const array FIELD_MAP = [
    'status' => TurnusTableMap::COL_STATUS,
    'familyId' => TurnusTableMap::COL_FAMILY_ID,
    'babysitterId' => TurnusTableMap::COL_BABYSITTER_ID,
    'dateFrom' => TurnusTableMap::COL_DATE_FROM,
    'dateTo' => TurnusTableMap::COL_DATE_TO,
    // dalsie povolene polia...
];
```

Pre datumy nepovolit tiche ulozenie `null` pri nevalidnom texte. Prazdna hodnota moze znamenat `NULL`, ale nevalidny text musi vratit validacnu chybu.

## Konfliktna ochrana

Minimalna verzia:

- posielat spolu s hodnotou aj povodnu hodnotu, ktoru mal browser pri nacitani alebo poslednom ulozeni,
- pred update overit, ci aktualna DB hodnota stale zodpoveda povodnej,
- ak nie, vratit konflikt a neprepisat DB.

Lepšia verzia:

- pridat `updated_at` alebo `version` stlpec na `sn_turnus`,
- kazdy autosave posiela poslednu znamu verziu,
- server ulozi zmenu iba ak verzia sedi,
- po ulozeni vrati novu verziu.

## Odporucany postup implementacie

1. Najprv urobit field-scoped autosave iba pre detail turnusu.
2. Zachovat existujuce CSS triedy `updateInput`, `updateSelect`, `updateDate`, ale pridat `data-field`.
3. Nahradit submit celeho formulara volanim noveho field endpointu.
4. Doplniť server-side allowlist a per-field parsery.
5. Pre datumy rozlisit prazdnu hodnotu a nevalidny format.
6. Doplniť per-control queue do JS.
7. Overit paralelne scenare: rychle zmeny dvoch poli, dva browser taby, nevalidny datum, reload selectov rodina/opatrovatelka.
8. Az po overeni zovseobecnit pattern pre dalsie migrovane autosave stranky.

## Co nerobit

- Nevracat sa ku klientskym `table` a `column` parametrom.
- Neposielat cely formular pre kazdu jednu zmenu.
- Neignorovat zmeny pocas prebiehajuceho requestu.
- Neulozit nevalidny datum ako `NULL` bez jasnej chyby v UI.
- Nemiesat jeden DB stlpec vo viacerych autosave formularoch na tej istej stranke.
