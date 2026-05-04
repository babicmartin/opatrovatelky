<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Entity\UserEntity;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserProfileUpdateForm;
use App\Model\Factory\BaseFactory;
use App\Model\Factory\UserFactory;
use App\Model\Table\ActiveTableMap;
use App\Model\Table\PermissionTableMap;
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

	public function updateProfile(int $userId, UserProfileUpdateForm $form): int
	{
		return $this->findAll()
			->where(UserTableMap::COL_ID, $userId)
			->update([
				UserTableMap::COL_NAME => $form->getName(),
				UserTableMap::COL_SECOND_NAME => $form->getSecondName(),
				UserTableMap::COL_ACRONYM => $form->getAcronym(),
				UserTableMap::COL_EMAIL => $form->getEmail(),
				UserTableMap::COL_COLOR => $form->getColor(),
			]);
	}

	public function updatePasswordHash(int $userId, string $passwordHash): int
	{
		return $this->findAll()
			->where(UserTableMap::COL_ID, $userId)
			->update([UserTableMap::COL_PASSWORD => $passwordHash]);
	}

	public function updateImage(int $userId, string $image): int
	{
		return $this->findAll()
			->where(UserTableMap::COL_ID, $userId)
			->update([UserTableMap::COL_IMAGE => $image]);
	}

	/**
	 * @return list<array{id:int,name:string,secondName:string,acronym:string,email:string,color:string,image:string,permission:int,permissionName:string,active:int,activeStatus:string}>
	 */
	public function findManagementRows(): array
	{
		$u = UserTableMap::TABLE_NAME;
		$p = PermissionTableMap::TABLE_NAME;
		$a = ActiveTableMap::TABLE_NAME;

		$rows = $this->database->query("
			SELECT
				u." . UserTableMap::COL_ID . " AS id,
				u." . UserTableMap::COL_NAME . " AS name,
				u." . UserTableMap::COL_SECOND_NAME . " AS second_name,
				u." . UserTableMap::COL_ACRONYM . " AS acronym,
				u." . UserTableMap::COL_EMAIL . " AS email,
				u." . UserTableMap::COL_COLOR . " AS color,
				u." . UserTableMap::COL_IMAGE . " AS image,
				u." . UserTableMap::COL_PERMISSION . " AS permission,
				COALESCE(p." . PermissionTableMap::COL_NAME . ", '') AS permission_name,
				u." . UserTableMap::COL_ACTIVE . " AS active,
				COALESCE(a." . ActiveTableMap::COL_STATUS . ", '') AS active_status
			FROM $u u
			LEFT JOIN $p p ON p." . PermissionTableMap::COL_PERMISSION . " = u." . UserTableMap::COL_PERMISSION . "
			LEFT JOIN $a a ON a." . ActiveTableMap::COL_ID . " = u." . UserTableMap::COL_ACTIVE . "
			ORDER BY u." . UserTableMap::COL_ID . " DESC
		")->fetchAll();

		return array_map(
			fn (object $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'secondName' => (string) ($row->second_name ?? ''),
				'acronym' => (string) ($row->acronym ?? ''),
				'email' => (string) ($row->email ?? ''),
				'color' => (string) ($row->color ?? ''),
				'image' => (string) ($row->image ?? ''),
				'permission' => (int) ($row->permission ?? 0),
				'permissionName' => (string) ($row->permission_name ?? ''),
				'active' => (int) ($row->active ?? 0),
				'activeStatus' => (string) ($row->active_status ?? ''),
			],
			$rows,
		);
	}

	public function updateAccess(int $userId, int $permission, int $active): int
	{
		return $this->findAll()
			->where(UserTableMap::COL_ID, $userId)
			->update([
				UserTableMap::COL_PERMISSION => $permission,
				UserTableMap::COL_ACTIVE => $active,
			]);
	}

	/**
	 * @return array<int, string>
	 */
	public function findPermissionOptions(bool $includeAdmin = true): array
	{
		$options = [];
		$rows = $this->database->table(PermissionTableMap::TABLE_NAME)
			->order(PermissionTableMap::COL_PERMISSION . ' ASC');

		if (!$includeAdmin) {
			$rows->where(PermissionTableMap::COL_PERMISSION . ' < ?', UserRole::ADMIN->getPermissionId());
		}

		foreach ($rows as $row) {
			$options[(int) $row->{PermissionTableMap::COL_PERMISSION}] = (string) $row->{PermissionTableMap::COL_NAME};
		}

		return $options;
	}

	/**
	 * @return array<int, string>
	 */
	public function findActiveOptions(): array
	{
		$options = [];
		$rows = $this->database->table(ActiveTableMap::TABLE_NAME)
			->order(ActiveTableMap::COL_ID . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{ActiveTableMap::COL_ID}] = (string) $row->{ActiveTableMap::COL_STATUS};
		}

		return $options;
	}

	public function createEmptyUser(string $passwordHash): int
	{
		$row = $this->insert([
			UserTableMap::COL_NAME => '',
			UserTableMap::COL_SECOND_NAME => '',
			UserTableMap::COL_ACRONYM => '',
			UserTableMap::COL_EMAIL => 'novy-uzivatel-' . date('YmdHis') . '@local.invalid',
			UserTableMap::COL_PASSWORD => $passwordHash,
			UserTableMap::COL_PERMISSION => UserRole::DEALER_JUNIOR->getPermissionId(),
			UserTableMap::COL_COLOR => '#8A2062',
			UserTableMap::COL_ACTIVE => 1,
			UserTableMap::COL_IMAGE => '',
		]);

		if (!$row instanceof ActiveRow) {
			throw new \RuntimeException('Používateľa sa nepodarilo vytvoriť.');
		}

		return (int) $row->{UserTableMap::COL_ID};
	}
}
