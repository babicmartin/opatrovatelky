<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Entity\UserEntity;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserProfileUpdateForm;
use App\Model\Repository\UserRepository;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class UserRepositoryTest extends DatabaseTestCase
{
	public function testUserRepositoryUpdatesProfileAccessPasswordImageAndBuildsOptions(): void
	{
		$repository = $this->getContainer()->getByType(UserRepository::class);
		$userId = $repository->createEmptyUser('initial-hash');
		$adminId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Admin',
			UserTableMap::COL_SECOND_NAME => 'Excluded',
			UserTableMap::COL_EMAIL => 'admin.excluded@example.test',
			UserTableMap::COL_PERMISSION => 10,
		]);

		$repository->updateProfile($userId, new UserProfileUpdateForm(
			'Agent',
			'Five',
			'A5',
			'agent.five@example.test',
			'#445566',
		));
		$repository->updatePasswordHash($userId, 'updated-hash');
		$repository->updateImage($userId, 'avatar.png');
		$repository->updateAccess($userId, 5, 2);

		$userRow = $repository->findById($userId);
		$userEntity = $repository->findById($userId, true);
		$managementRows = $repository->findManagementRows();
		$managementRow = array_values(array_filter(
			$managementRows,
			static fn (array $row): bool => $row['id'] === $userId,
		))[0] ?? null;

		self::assertNotNull($userRow);
		self::assertSame('Agent', $userRow->{UserTableMap::COL_NAME});
		self::assertSame('Five', $userRow->{UserTableMap::COL_SECOND_NAME});
		self::assertSame('A5', $userRow->{UserTableMap::COL_ACRONYM});
		self::assertSame('agent.five@example.test', $userRow->{UserTableMap::COL_EMAIL});
		self::assertSame('updated-hash', $userRow->{UserTableMap::COL_PASSWORD});
		self::assertSame('avatar.png', $userRow->{UserTableMap::COL_IMAGE});
		self::assertSame(5, (int) $userRow->{UserTableMap::COL_PERMISSION});
		self::assertSame(2, (int) $userRow->{UserTableMap::COL_ACTIVE});
		self::assertInstanceOf(UserEntity::class, $userEntity);
		self::assertSame($userId, $userEntity->getId());
		self::assertSame('Agent Five', $repository->findSelectOptions()[$userId]);
		self::assertArrayNotHasKey($adminId, $repository->findSelectOptions());
		self::assertArrayNotHasKey(10, $repository->findPermissionOptions(false));
		self::assertSame('CEO', $repository->findPermissionOptions(false)[5]);
		self::assertSame('Neaktívny', $repository->findActiveOptions()[2]);
		self::assertNotNull($managementRow);
		self::assertSame('CEO', $managementRow['permissionName']);
		self::assertSame('Neaktívny', $managementRow['activeStatus']);
	}
}
