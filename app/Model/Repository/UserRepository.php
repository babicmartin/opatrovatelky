<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Entity\UserEntity;
use App\Model\Factory\BaseFactory;
use App\Model\Factory\UserFactory;
use App\Model\Table\UserTableMap;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

class UserRepository extends BaseRepository
{
	public function __construct(
		Explorer $database,
		private readonly UserFactory $userFactory,
	) {
		parent::__construct($database);
	}

	protected function getTableName(): string
	{
		return UserTableMap::TABLE_NAME;
	}

	protected function getFactory(): BaseFactory
	{
		return $this->userFactory;
	}

	/**
	 * @return ($wrapToEntity is true ? UserEntity|null : ActiveRow|null)
	 */
	public function findById(int $id, bool $wrapToEntity = false): ActiveRow|UserEntity|null
	{
		$row = $this->getItem($id);

		if ($row === null || !$wrapToEntity) {
			return $row;
		}

		/** @var UserEntity */
		return $this->userFactory->createFromActiveRow($row);
	}

	/**
	 * @return array<int, string>
	 */
	public function findSelectOptions(): array
	{
		$options = [0 => '---'];
		$rows = $this->findAll()
			->where(UserTableMap::COL_PERMISSION . ' < ?', 10)
			->order(UserTableMap::COL_SECOND_NAME . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->id] = trim((string) $row->{UserTableMap::COL_NAME} . ' ' . (string) $row->{UserTableMap::COL_SECOND_NAME});
		}

		return $options;
	}
}
