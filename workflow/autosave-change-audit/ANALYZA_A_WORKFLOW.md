# Autosave partial update a evidencia zmien

## Aktualny stav

- `www/js/autosaveForm.js` dnes pri autosave odosiela `new FormData(form)`, teda cely formular.
- Serverove handlery potom vacsinou volaju repozitarove metody typu `updateFromForm()` alebo `updateDocument()`, ktore ukladaju viac stlpcov naraz.
- Cast formularov uz rozlisuje podmienene odoslane polia cez `hasSubmittedControl()`, ale nie je to jednotne a stale to nevyriesi audit.
- Autosave pouzivaju formularove factory pod `app/UI/Admin/Form` aj dokumentove a evidencne kontrolky pod `app/UI/Admin/Control`.

## Ciel

- Pri zmene inputu, selectu, checkboxu alebo checkbox listu ulozit iba konkretne zmenene pole.
- Pri kazdej realnej zmene zapisat audit: kto zmenu urobil, kedy, v akom kontexte, ktore pole, povodna hodnota a nova hodnota.
- Pri selectoch a ciselnikoch ulozit ID aj citatelny label starej a novej hodnoty.
- Ponechat povodny full-form autosave v `autosaveForm.js` ako zakomentovany legacy blok a fallback pre formular bez partial kontextu.

## SQL tabulka

```sql
CREATE TABLE IF NOT EXISTS `sn_change_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `context` VARCHAR(100) NOT NULL,
  `entity_table` VARCHAR(100) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `field_name` VARCHAR(100) NOT NULL,
  `field_label` VARCHAR(190) NOT NULL,
  `column_name` VARCHAR(100) DEFAULT NULL,
  `value_type` VARCHAR(30) NOT NULL DEFAULT 'text',
  `old_value_id` VARCHAR(100) DEFAULT NULL,
  `old_value_label` LONGTEXT DEFAULT NULL,
  `new_value_id` VARCHAR(100) DEFAULT NULL,
  `new_value_label` LONGTEXT DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` LONGTEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_change_log_created_at` (`created_at`),
  KEY `idx_change_log_user_created_at` (`user_id`, `created_at`),
  KEY `idx_change_log_entity_created_at` (`entity_table`, `entity_id`, `created_at`),
  KEY `idx_change_log_context_created_at` (`context`, `created_at`),
  KEY `idx_change_log_field_created_at` (`field_name`, `created_at`),
  CONSTRAINT `chk_sn_change_log_metadata_json`
    CHECK (`metadata` IS NULL OR JSON_VALID(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Implementacny postup

1. Vytvorit migracny SQL subor pre `sn_change_log`.
2. Pridat `ChangeLogTableMap` a `ChangeLogRepository`.
3. Pridat centralnu partial autosave sluzbu, ktora mapuje `context + field` na tabulku, stlpec, label a typ hodnoty.
4. Upravit `autosaveForm.js`, aby aktivne odosielal iba zmenene pole, hidden polia a metadokumentacne polia `__autosave_context`, `__autosave_field`, `__autosave_checked`.
5. Ponechat povodny full-form autosave v `autosaveForm.js` zakomentovany ako legacy rollback pomocku.
6. Pouzit `data-autosave-context` tam, kde bude explicitne doplneny, inak mapovat existujuce CSS triedy formularov v `autosaveForm.js`.
7. V autosave handlery najprv skusit partial autosave sluzbu; ak request nema partial metadata, ponechat existujuce full-form ulozenie.
8. Pridat admin stranku `Evidencia zmien` v `Nastavenia`, s paginatorom a pristupom iba pre CEO/admin.

## Akceptacne scenare

- Zmena jedneho textoveho pola ulozi iba jeden DB stlpec a zapise audit.
- Zmena selectu zapise povodne/nove ID aj label.
- Zmena checkboxu zapise `0/1` aj `Nie/Ano`.
- Zmena checkbox listu zapise delta zmenu cez `metadata`.
- Bez realnej zmeny hodnoty nevznikne audit zaznam.
- Formular bez `data-autosave-context` alebo znamej CSS triedy pouzije legacy full-form autosave.
- CEO/admin vidia `Nastavenia -> Evidencia zmien`; nizsie role nie.

## Implementovane subory

- `migrations/20260530_create_sn_change_log.sql`
- `app/Model/Table/ChangeLogTableMap.php`
- `app/Model/Repository/ChangeLogRepository.php`
- `app/Model/Service/Autosave/AutosaveFieldUpdateService.php`
- `www/js/autosaveForm.js`
- `app/UI/Admin/AdminPresenter.php`
- `app/UI/Admin/ChangeLog/ChangeLogPresenter.php`
- `app/UI/Admin/ChangeLog/templates/ChangeLog.default.latte`
- `app/Model/Enum/Acl/Resource.php`
- `app/Model/Security/Authorizator/AuthorizatorFactory.php`
- `app/Router/RouterFactory.php`
- `app/UI/Admin/Settings/templates/Settings.default.latte`
