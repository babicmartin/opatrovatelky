<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Entity\PageEntity;
use App\Model\Factory\BaseFactory;
use App\Model\Factory\PageFactory;
use App\Model\Table\PageTableMap;
use App\Model\Utils\Date\DateService;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class PageRepository extends BaseRepository
{
	public function __construct(
		Explorer $database,
		DateService $dateService,
		private readonly PageFactory $pageFactory,
	) {
		parent::__construct($database, $dateService);
	}

	protected function getTableName(): string
	{
		return PageTableMap::TABLE_NAME;
	}

	protected function getFactory(): BaseFactory
	{
		return $this->pageFactory;
	}

	/**
	 * Top-level menu rows in display order. Who may see a row is decided by the menu control
	 * from the role's permissions; the `permission` column is not read.
	 *
	 * @return ($wrapToEntity is true ? list<PageEntity> : Selection<ActiveRow>)
	 */
	public function findMenuItems(bool $wrapToEntity = false): Selection|array
	{
		$selection = $this->findAll()
			->where(PageTableMap::COL_ACTIVE, 1)
			->where(PageTableMap::COL_PARENT, 0)
			->where(PageTableMap::COL_POSITION . ' > ?', 0)
			->where(PageTableMap::COL_IN_MENU, 1)
			->order(PageTableMap::COL_POSITION . ' ASC');

		if ($wrapToEntity) {
			/** @var list<PageEntity> */
			return $this->wrapToEntities($selection);
		}

		return $selection;
	}
}
