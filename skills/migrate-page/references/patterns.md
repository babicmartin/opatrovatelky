# Code Patterns Reference

All templates use placeholders: `{Domain}`, `{domain}`, `{TABLE_NAME}`, `{col_name}`.

---

## TableMap

File: `app/Model/Table/{Domain}TableMap.php`

```php
<?php

declare(strict_types=1);

namespace App\Model\Table;

class {Domain}TableMap extends BaseTableMap
{
    public const string TABLE_NAME = '{TABLE_NAME}';
    public const string TABLE_PREFIX = '{domain}_mapper';

    public const string COL_ID = 'id';
    // Add COL_* constants for each DB column (SCREAMING_SNAKE_CASE)
}
```

---

## Entity

File: `app/Model/Entity/{Domain}Entity.php`

```php
<?php

declare(strict_types=1);

namespace App\Model\Entity;

class {Domain}Entity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        // All other properties: nullable with get/set hooks
        private ?string $name {
            get { return $this->name; }
            set { $this->name = $value; }
        },
        // For int columns:
        private ?int $active {
            get { return $this->active; }
            set { $this->active = $value; }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
```

---

## Factory

File: `app/Model/Factory/{Domain}Factory.php`

```php
<?php

declare(strict_types=1);

namespace App\Model\Factory;

class {Domain}Factory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return \App\Model\Entity\{Domain}Entity::class;
    }

    protected function getTableMapClass(): string
    {
        return \App\Model\Table\{Domain}TableMap::class;
    }
}
```

---

## Repository

File: `app/Model/Repository/{Domain}Repository.php`

```php
<?php

declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Factory\{Domain}Factory;
use App\Model\Table\{Domain}TableMap;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class {Domain}Repository extends BaseRepository
{
    public function __construct(
        Explorer $database,
        private readonly {Domain}Factory ${domain}Factory,
    ) {
        parent::__construct($database);
    }

    protected function getTableName(): string
    {
        return {Domain}TableMap::TABLE_NAME;
    }

    protected function getFactory(): BaseFactory
    {
        return $this->{domain}Factory;
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->getItem($id);
    }

    /**
     * @return Selection
     */
    public function findAllActive(): Selection
    {
        return $this->findAll()
            ->where({Domain}TableMap::COL_ACTIVE, 1)
            ->where({Domain}TableMap::COL_DELETED, 0);
    }

    /**
     * Filtered list for list view. Adjust parameters based on actual filters.
     */
    public function findFiltered(
        ?int $countryId = null,
        ?int $statusId = null,
        ?string $search = null,
        string $orderBy = 'id',
        string $orderDir = 'DESC',
    ): Selection {
        $selection = $this->findAllActive();

        if ($countryId !== null) {
            $selection->where({Domain}TableMap::COL_COUNTRY_ID, $countryId);
        }

        if ($statusId !== null) {
            $selection->where({Domain}TableMap::COL_STATUS_ID, $statusId);
        }

        if ($search !== null && $search !== '') {
            $selection->where(
                {Domain}TableMap::COL_NAME . ' LIKE ?',
                '%' . $search . '%',
            );
        }

        return $selection->order($orderBy . ' ' . $orderDir);
    }
}
```

---

## Control (4 files)

### Control class

File: `app/UI/Admin/Control/{Theme}/{Name}/{Name}Control.php`

```php
<?php

declare(strict_types=1);

namespace App\UI\Admin\Control\{Theme}\{Name};

use App\Model\Repository\{Domain}Repository;
use Nette\Application\UI\Control;

class {Name}Control extends Control
{
    // For list controls with pagination/filters - use setters:
    private int $page = 1;

    public function __construct(
        private readonly {Domain}Repository ${domain}Repository,
        // Add other dependencies (PaginatorFactory, StorageDirProvider, etc.)
    ) {
    }

    public function setPage(int $page): static
    {
        $this->page = $page;
        return $this;
    }

    public function render(): void
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/{Name}Control.latte');

        // Assign template variables
        $template->items = $this->{domain}Repository->findAllActive();

        $template->render();
    }
}
```

### Control with Paginator

```php
use App\Model\Factory\PaginatorFactory;

class {Name}Control extends Control
{
    private int $page = 1;

    public function __construct(
        private readonly {Domain}Repository ${domain}Repository,
        private readonly PaginatorFactory $paginatorFactory,
    ) {
    }

    public function setPage(int $page): static
    {
        $this->page = $page;
        return $this;
    }

    public function render(): void
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/{Name}Control.latte');

        $itemsPerPage = 20;
        $selection = $this->{domain}Repository->findAllActive();
        $totalCount = $selection->count('*');

        $paginator = $this->paginatorFactory->create(
            $this->page,
            $totalCount,
            $itemsPerPage,
            'Admin:{Domain}:default',
        );

        $items = $selection->limit($paginator->getItemsPerPage(), $paginator->getOffset());

        $template->items = $items;
        $template->paginator = $paginator;

        $template->render();
    }
}
```

### Factory interface

File: `app/UI/Admin/Control/{Theme}/{Name}/{Name}ControlFactory.php`

```php
<?php

declare(strict_types=1);

namespace App\UI\Admin\Control\{Theme}\{Name};

interface {Name}ControlFactory
{
    public function create(): {Name}Control;
}
```

For controls needing runtime params:

```php
interface {Name}ControlFactory
{
    public function create(int $id): {Name}Control;
}
```

### Presenter Trait

File: `app/UI/Admin/Control/{Theme}/{Name}/{Name}PresenterTrait.php`

```php
<?php

declare(strict_types=1);

namespace App\UI\Admin\Control\{Theme}\{Name};

trait {Name}PresenterTrait
{
    private {Name}ControlFactory ${name}ControlFactory;

    public function inject{Name}Control({Name}ControlFactory ${name}ControlFactory): void
    {
        $this->{name}ControlFactory = ${name}ControlFactory;
    }

    protected function createComponent{Name}(): {Name}Control
    {
        return $this->{name}ControlFactory->create();
    }
}
```

For list controls with page setter:

```php
protected function createComponent{Name}(): {Name}Control
{
    $control = $this->{name}ControlFactory->create();
    $control->setPage($this->getParameter('page') ? (int) $this->getParameter('page') : 1);
    return $control;
}
```

For controls needing ID:

```php
protected function createComponent{Name}(): {Name}Control
{
    $id = (int) $this->getParameter('id');
    return $this->{name}ControlFactory->create($id);
}
```

### Latte template

File: `app/UI/Admin/Control/{Theme}/{Name}/templates/{Name}Control.latte`

```latte
{varType Nette\Database\Table\Selection $items}

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{Title}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        {* Add columns *}
                    </tr>
                </thead>
                <tbody>
                    {foreach $items as $item}
                        <tr>
                            <td>{$item->id}</td>
                            <td>{$item->name}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
```

---

## Paginator Latte (shared template)

File: `app/UI/Admin/templates/components/paginator.latte`

```latte
{*
 * Shared paginator component.
 * Usage: {include '../templates/components/paginator.latte', paginator: $paginator}
 *}
{varType App\Model\Utils\Paginator\Paginator $paginator}

{if $paginator->hasMultiplePages()}
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <li class="page-item {if $paginator->getCurrentPage() === 1}disabled{/if}">
            <a class="page-link" n:href="{$paginator->getRoute()}, ...$paginator->getRouteParamsForPage($paginator->getCurrentPage() - 1)">
                &laquo;
            </a>
        </li>

        {for $i = 1; $i <= $paginator->getTotalPages(); $i++}
            <li class="page-item {if $i === $paginator->getCurrentPage()}active{/if}">
                <a class="page-link" n:href="{$paginator->getRoute()}, ...$paginator->getRouteParamsForPage($i)">
                    {$i}
                </a>
            </li>
        {/for}

        <li class="page-item {if $paginator->getCurrentPage() === $paginator->getTotalPages()}disabled{/if}">
            <a class="page-link" n:href="{$paginator->getRoute()}, ...$paginator->getRouteParamsForPage($paginator->getCurrentPage() + 1)">
                &raquo;
            </a>
        </li>
    </ul>
</nav>
{/if}
```

---

## Presenter

File: `app/UI/Admin/{Domain}/{Domain}Presenter.php`

```php
<?php

declare(strict_types=1);

namespace App\UI\Admin\{Domain};

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\{Domain}\{Domain}List\{Domain}ListPresenterTrait;

class {Domain}Presenter extends AdminPresenter
{
    use {Domain}ListPresenterTrait;
    // Add more traits for detail controls, forms, etc.

    protected function getResource(): string
    {
        return Resource::{DOMAIN}->value;
    }

    public function actionDefault(int $page = 1): void
    {
        // Filters and page are passed to control via trait's createComponent
    }

    public function actionDetail(int $id): void
    {
        // ID is passed to control via trait's createComponent
    }
}
```

### Presenter templates

File: `app/UI/Admin/{Domain}/templates/{Domain}.default.latte`

```latte
{block content}
    {control {domain}List}
{/block}
```

File: `app/UI/Admin/{Domain}/templates/{Domain}.detail.latte`

```latte
{block content}
    {control {domain}Detail}
{/block}
```

---

## Form

### FormFactory

File: `app/UI/Admin/Form/{Domain}/{FormName}/{FormName}FormFactory.php`

```php
<?php

declare(strict_types=1);

namespace App\UI\Admin\Form\{Domain}\{FormName};

use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;

class {FormName}FormFactory
{
    public function __construct(
        private readonly BaseFormFactory $baseFormFactory,
    ) {
    }

    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name', 'Name')
            ->setRequired('Please enter name.');

        // Add more fields...

        $form->addSubmit('submit', 'Save');

        return $form;
    }
}
```

### Form DTO

File: `app/Model/Form/DTO/Admin/{Domain}/{FormName}/{FormName}FormDTO.php`

```php
<?php

declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\{Domain}\{FormName};

final readonly class {FormName}FormDTO
{
    public function __construct(
        private string $name,
        // Add properties matching form fields
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

---

## Config .neon

File: `config/control/{controlName}.neon`

```neon
services:
    -
        implement: App\UI\Admin\Control\{Theme}\{Name}\{Name}ControlFactory
```

Add to `config/includes.neon`:

```neon
    #control
    - control/{controlName}.neon
```

---

## Router entry

In `app/Router/RouterFactory.php`, add inside the Admin module block:

```php
$router->withModule('Admin')
    ->addRoute('{domain}/', '{Domain}:default')
    ->addRoute('{domain}/<id [0-9]+>/', '{Domain}:detail');
```

---

## Filter pattern (old -> new)

Old controller:
```php
$countryId = isset($_GET['country']) ? (int) $_GET['country'] : null;
```

New presenter action:
```php
public function actionDefault(int $page = 1, ?int $country = null, ?int $status = null): void
{
}
```

New trait createComponent:
```php
protected function createComponent{Name}(): {Name}Control
{
    $control = $this->{name}ControlFactory->create();
    $control->setPage((int) ($this->getParameter('page') ?? 1));
    $control->setCountry($this->getParameter('country') !== null ? (int) $this->getParameter('country') : null);
    return $control;
}
```

---

## Signal pattern (AJAX action replacement)

Old: `$.ajax({ url: 'ajax/hideTurnus.php', data: ... })`

New (in Control):
```php
public function handleDelete(int $id): void
{
    $this->{domain}Repository->softDelete($id);
    
    if ($this->getPresenter()->isAjax()) {
        $this->redrawControl();
    } else {
        $this->getPresenter()->redirect('this');
    }
}
```

In Latte:
```latte
<a n:href="delete! $item->id" class="btn btn-sm btn-danger"
   data-naja
   data-confirm="Are you sure?">Delete</a>
```
