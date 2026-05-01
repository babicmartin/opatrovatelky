# Konvencie pre UI Controls

## Adresarova struktura

Controls sa ukladaju do tematickych adresarov:

```
app/UI/{Modul}/Control/{Tema}/{NazovControlky}/
├── {Nazov}Control.php              # Control trieda
├── {Nazov}ControlFactory.php       # Factory interface
├── {Nazov}PresenterTrait.php       # Trait pre presenter
└── templates/
    └── {Nazov}Control.latte        # Latte sablona
```

### Aktualne kontrolky

```
app/UI/Admin/Control/
├── Layout/
│   ├── Menu/                    # Hlavne menu z DB (sany_pages)
│   ├── Search/                  # Vyhladavacie inputy
│   └── Toolbar/                 # Toolbar ikony
└── User/
    └── UserProfileImage/        # Profilovy obrazok uzivatela
```

## Factory pattern (standard)

Kazda controlka pouziva **factory pattern** cez DI container.

### 1. Control trieda

```php
class MenuControl extends Nette\Application\UI\Control
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly User $user,
    ) {
    }

    public function render(): void
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/MenuControl.latte');
        // ... nastavenie premennych
        $template->render();
    }
}
```

### 2. Factory interface

```php
interface MenuControlFactory
{
    public function create(): MenuControl;
}
```

- Vsetky zavislosti sa injektuju cez constructor (DI container)
- Metoda `create()` nema parametre (vynimka: entity ako `UserEntity $userEntity`)

### 3. Presenter Trait

```php
trait MenuPresenterTrait
{
    private MenuControlFactory $menuControlFactory;

    public function injectMenuControl(MenuControlFactory $menuControlFactory): void
    {
        $this->menuControlFactory = $menuControlFactory;
    }

    protected function createComponentMenu(): MenuControl
    {
        return $this->menuControlFactory->create();
    }
}
```

- `inject*` metoda — Nette DI ju vola automaticky na presenteroch
- `createComponent{Nazov}` — Nette vola pri `{control nazov}` v sablone

### 4. DI registracia

Novy `.neon` subor v `config/control/`:

```neon
services:
    -
        implement: App\UI\Admin\Control\Layout\Menu\MenuControlFactory
```

Pridat do `config/includes.neon`:

```neon
    #control
    - control/menu.neon
```

**Dolezite:** `*ControlFactory` su excludovane z auto-discovery v `services.neon`, preto sa registruju manualne.

## Setter pattern (iba pre strankovanie a filtre)

Setter pattern sa pouziva **vyhradne** pre controls so strankovanim alebo filtrami:

```php
class DataGridControl extends Control
{
    private int $page = 1;
    private ?string $filter = null;

    public function setPage(int $page): static
    {
        $this->page = $page;
        return $this;
    }

    public function setFilter(?string $filter): static
    {
        $this->filter = $filter;
        return $this;
    }
}
```

Pre bezne layout controls (menu, user profil, search, toolbar) sa setter pattern **nepouziva**.

## Entity v factory

Ak control potrebuje pracovat s entitou, posle sa cez factory parameter:

```php
// Factory interface
interface UserProfileImageControlFactory
{
    public function create(UserEntity $userEntity): UserProfileImageControl;
}

// V presenter traite
protected function createComponentUserProfileImage(): UserProfileImageControl
{
    $userEntity = $this->userRepository->findById($userId, wrapToEntity: true);
    return $this->factory->create($userEntity);
}
```

Control nikdy nesmie sam ziskavat identity alebo queryovat DB pre zakladne data.

## Repository wrapToEntity pattern

Repositories podporuju automaticke wrapping do entit:

```php
// Vrati Selection (ActiveRow)
$rows = $pageRepository->findMenuItems($permission);

// Vrati array entit
$entities = $pageRepository->findMenuItems($permission, wrapToEntity: true);
```

Entity wrapping je implementovane v `BaseRepository::wrapToEntities()` a `BaseFactory::createEntitiesFromRows()`.

## StorageDirProvider

Nepouzivat hardcoded cesty! Pouzivat `StorageDirProvider`:

```php
// Spravne
$this->storageDirProvider->getUserImages() . '/' . $image
$this->storageDirProvider->getUserImagesEmpty()

// Zle
'img/users/' . $image
'img/users/empty.jpg'
```

Cesty sa konfiguruju v `config/parameter.neon`.

## Nette Authorizator

Presenter authorization cez `$user->isAllowed()`:

```php
// V AdminPresenter
protected function getResource(): ?string { return null; }
protected function getPrivilege(): string { return 'default'; }

// V child presenteri
protected function getResource(): string { return Resource::HOME->value; }
```

Role mapovanie: `UserRole::fromPermissionId(int)` / `UserRole::getPermissionId(): int`

## Pouzitie v sablone

```latte
{control menu}
{control search}
{control toolbar}
{control userProfileImage}
```

## Opakovane formulare cez Multiplier

Pre opakovane riadkove formulare pouzivaj `Nette\Application\UI\Multiplier`. V sablone musi byt nazov dynamickej komponenty string:

```latte
{form "registryForm-$row[id]"}
	{input userId}
	{input save}
{/form}
```

Nepouzivaj `{form registryForm-$row['id']}`. Latte to vyhodnoti ako odcitanie a skonci chybou `Unsupported operand types: string - int`.

Pri migracii legacy inline editacie zachovaj triedy `updateSelect`, `updateDate`, `updateInput`, `updateCheckbox`, ale autosave aktivuj bezpecne cez explicitne triedy `js-autosave-form` na formulari a `js-autosave-control` na poliach. Neposielaj z klienta nazov tabulky ani stlpca. Klient posiela iba konkretny Nette formular s CSRF tokenom a server rozhoduje v konkretnej `FormFactory`/`onSuccess`, co sa smie ulozit. Riadkovy formular sa ma odoslat pri zmene select/date/checkbox a pri zmene alebo odchode z inputu/textarea. Pouzi AJAX request s `X-Requested-With: XMLHttpRequest`, aby Nette `onSuccess` ulozil data bez reloadu, a zobraz border feedback pocas ukladania aj po uspesnom ulozeni.

## Page title

Kazda admin presenter sablona musi mat vyplneny layoutovy nadpis:

```latte
{block pageTitle}Nazov stranky{/block}
```

## Overenie UI

Po zmene controlky alebo sablony nestaci PHPStan. Spusti lokalnu stranku cez `http://opatrovatelky.local/...` alebo lokalny PHP server a over:

- HTTP status je 200
- odpoved neobsahuje `tracy-section--error`
- opakovane komponenty/formulare sa realne vyrenderovali
- navigacne linky v toolbar/menu smeruju na novu route

Ak admin login je docasne vypnuty v `AdminPresenter`, layout controlky musia mat vyvojovy fallback pre neprihlaseneho pouzivatela, inak sa stranka neda runtime overit.

## Checklist pre novu controlku

1. Vytvor adresar `app/UI/{Modul}/Control/{Tema}/{Nazov}/`
2. Vytvor `{Nazov}Control.php` — extends `Nette\Application\UI\Control`
3. Vytvor `{Nazov}ControlFactory.php` — interface s `create()`
4. Vytvor `{Nazov}PresenterTrait.php` — inject + createComponent
5. Vytvor `templates/{Nazov}Control.latte`
6. Vytvor `config/control/{nazov}.neon` s `implement:`
7. Pridaj `.neon` do `config/includes.neon`
8. Pridaj `use {Nazov}PresenterTrait` do prislusneho presentera
9. Pouzi `{control nazov}` v `.latte` sablone
10. Spusti PHPStan
