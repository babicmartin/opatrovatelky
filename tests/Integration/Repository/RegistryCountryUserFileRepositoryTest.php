<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Entity\UserEntity;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserProfileUpdateForm;
use App\Model\Repository\CountryRepository;
use App\Model\Repository\FileRepository;
use App\Model\Repository\MissingRegistryRepository;
use App\Model\Repository\UserRepository;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class RegistryCountryUserFileRepositoryTest extends DatabaseTestCase
{
	public function testMissingRegistryRepositoryCreatesFindsUpdatesAndSoftDeletesRows(): void
	{
		$repository = $this->getContainer()->getByType(MissingRegistryRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'missing.registry@example.test',
		]);
		$emptyId = $repository->createEmpty();
		$otherId = TestDatabase::insert(MissingRegistryTableMap::TABLE_NAME, [
			MissingRegistryTableMap::COL_USER_ID => $userId,
			MissingRegistryTableMap::COL_DATE_FROM => '2026-06-10',
			MissingRegistryTableMap::COL_DATE_TO => '2026-06-11',
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);

		$lastEmpty = $repository->findLastEmptyRow();
		$pageCount = 0;
		$visibleRows = $repository->findVisibleRows(1, 10, $emptyId, $pageCount);

		$repository->updateRegistryRow($emptyId, [
			'userId' => $userId,
			'dateFrom' => new \DateTimeImmutable('2026-07-01'),
			'dateTo' => new \DateTimeImmutable('2026-07-03'),
			'typePn' => true,
			'typeOcr' => false,
			'typeLekar' => true,
			'typeSviatok' => false,
			'typeZastup' => true,
			'typeSluzba' => false,
			'typeDovolenka' => true,
			'notice' => 'Registry notice',
		]);
		$repository->softDelete($otherId);

		self::assertNotNull($lastEmpty);
		self::assertSame($emptyId, $lastEmpty['id']);
		self::assertSame(1, $pageCount);
		self::assertCount(1, $visibleRows);
		self::assertSame($otherId, $visibleRows[0]['id']);

		$updatedRow = $repository->findVisibleRows(1, 10, null)[0];
		self::assertSame($emptyId, $updatedRow['id']);
		self::assertSame($userId, $updatedRow['userId']);
		self::assertSame('2026-07-01', $updatedRow['dateFrom']->format('Y-m-d'));
		self::assertTrue($updatedRow['typePn']);
		self::assertFalse($updatedRow['typeOcr']);
		self::assertTrue($updatedRow['typeLekar']);
		self::assertTrue($updatedRow['typeZastup']);
		self::assertTrue($updatedRow['typeDovolenka']);
		self::assertSame('Registry notice', $updatedRow['notice']);
		self::assertSame(1, (int) $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->get($otherId)?->{MissingRegistryTableMap::COL_DELETED});
	}

	public function testCountryRepositoryCreatesUpdatesAndMapsActiveRows(): void
	{
		$repository = $this->getContainer()->getByType(CountryRepository::class);
		$countryId = $repository->createEmpty();

		$repository->updateTextFields($countryId, [
			'name' => 'Cesko',
			'german' => 'Tschechien',
		]);
		$repository->updateImage($countryId, 'cz.png');

		$row = $repository->findRowById($countryId);
		$activeRows = $repository->findActiveRows();

		self::assertNotNull($row);
		self::assertSame('Cesko', $row['name']);
		self::assertSame('Tschechien', $row['german']);
		self::assertSame('cz.png', $row['image']);
		self::assertSame(1, $row['active']);
		self::assertSame($countryId, $activeRows[0]['id']);
		self::assertSame('Cesko', $activeRows[0]['name']);
	}

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

	public function testFileRepositoryFindsUpdatesAndSoftDeletesDocuments(): void
	{
		$repository = $this->getContainer()->getByType(FileRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_ACRONYM => 'FD',
			UserTableMap::COL_COLOR => '#778899',
			UserTableMap::COL_EMAIL => 'file.documents@example.test',
		]);
		$documentId = TestDatabase::createFile([
			FileTableMap::COL_DIR => 'families-orders/55',
			FileTableMap::COL_NAME => 'contract.pdf',
			FileTableMap::COL_TYPE => 'application/pdf',
			FileTableMap::COL_USER => $userId,
			FileTableMap::COL_UPLOAD => '2026-06-05 14:30:00',
			FileTableMap::COL_VALID_FROM => '2026-06-01',
			FileTableMap::COL_VALID_TO => '2026-06-30',
			FileTableMap::COL_NOTICE => 'Initial notice',
			FileTableMap::COL_STATUS => 1,
		]);
		TestDatabase::createFile([
			FileTableMap::COL_DIR => 'families-orders/55',
			FileTableMap::COL_NAME => 'inactive.pdf',
			FileTableMap::COL_ACTIVE => 0,
		]);

		$documents = $repository->findDocuments('families-orders', 55);
		$document = $repository->findDocument('families-orders', 55, $documentId);
		$insertedId = $repository->insertDocument('families-orders', 55, 'new.pdf', 'application/pdf', $userId);
		$repository->updateDocument($documentId, [
			'notice' => 'Updated notice',
			'validFrom' => new \DateTimeImmutable('2026-07-01'),
			'validTo' => new \DateTimeImmutable('2026-07-31'),
			'status' => 1,
		]);
		$repository->softDelete($insertedId);

		self::assertCount(1, $documents);
		self::assertSame($documentId, $documents[0]['id']);
		self::assertSame('Nahraté 05.06.2026 o 00:00', $documents[0]['upload']);
		self::assertSame('FD', $documents[0]['userAcronym']);
		self::assertSame('#778899', $documents[0]['userColor']);
		self::assertNotNull($document);
		self::assertSame('Initial notice', $document['notice']);
		self::assertSame('Prijatý', $repository->findStatusOptions()[1]);

		$updatedDocument = $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($documentId);
		$insertedDocument = $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($insertedId);
		self::assertNotNull($updatedDocument);
		self::assertSame('Updated notice', $updatedDocument->{FileTableMap::COL_NOTICE});
		self::assertSame('2026-07-01', $updatedDocument->{FileTableMap::COL_VALID_FROM}->format('Y-m-d'));
		self::assertNotNull($insertedDocument);
		self::assertSame('families-orders/55', $insertedDocument->{FileTableMap::COL_DIR});
		self::assertSame(0, (int) $insertedDocument->{FileTableMap::COL_ACTIVE});
	}
}
