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
	 * @return ($wrapToEntity is true ? list<PageEntity> : Selection<ActiveRow>)
	 */
	public function findMenuItems(?int $userPermission = null, bool $wrapToEntity = false): Selection|array
	{
		$selection = $this->findAll()
			->where(PageTableMap::COL_ACTIVE, 1)
			->where(PageTableMap::COL_PARENT, 0)
			->where(PageTableMap::COL_POSITION . ' > ?', 0)
			->where(PageTableMap::COL_IN_MENU, 1)
			->order(PageTableMap::COL_POSITION . ' ASC');

		if ($userPermission !== null) {
			$selection->where(PageTableMap::COL_PERMISSION . ' <= ?', $userPermission);
		}

		if ($wrapToEntity) {
			/** @var list<PageEntity> */
			return $this->wrapToEntities($selection);
		}

		return $selection;
	}

	/**
	 * @return list<PageEntity>
	 */
	public function getAll(): array
	{
		/** @var list<PageEntity> */
		return $this->wrapToEntities($this->findAll());
	}
}
