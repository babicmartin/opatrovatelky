<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\ChangeLogRepository;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class ChangeLogRepositoryTest extends DatabaseTestCase
{
	public function testLogChangeStoresAndMapsComputedRowData(): void
	{
		$repository = $this->getContainer()->getByType(ChangeLogRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Admin',
			UserTableMap::COL_SECOND_NAME => 'Tester',
			UserTableMap::COL_EMAIL => 'admin.tester@example.test',
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-901',
			OpatrovatelkaTableMap::COL_NAME => 'Jana',
			OpatrovatelkaTableMap::COL_SURNAME => 'Auditova',
		]);

		$repository->logChange([
			'context' => 'babysitter.main',
			'entityTable' => OpatrovatelkaTableMap::TABLE_NAME,
			'entityId' => $babysitterId,
			'fieldName' => 'notice',
			'fieldLabel' => 'Poznámka',
			'columnName' => 'notice',
			'valueType' => 'text',
			'oldValueId' => null,
			'oldValueLabel' => 'Stará hodnota',
			'newValueId' => null,
			'newValueLabel' => 'Nová hodnota',
			'userId' => $userId,
			'metadata' => ['action' => 'updated', 'source' => 'phpunit'],
		]);

		$pageCount = 0;
		$rows = $repository->findRows(1, 10, $pageCount);

		self::assertSame(1, $pageCount);
		self::assertCount(1, $rows);

		$row = $rows[0];
		self::assertSame('babysitter.main', $row['context']);
		self::assertSame('Poznámka', $row['fieldLabel']);
		self::assertSame('Text', $row['valueTypeLabel']);
		self::assertSame('Stará hodnota', $row['oldValueLabel']);
		self::assertSame('Nová hodnota', $row['newValueLabel']);
		self::assertSame($userId, $row['userId']);
		self::assertSame('Admin Tester', $row['userName']);
		self::assertSame('{"action":"updated","source":"phpunit"}', $row['metadata']);
		self::assertSame('Opatrovateľky / pracovníci', $row['sectionLabel']);
		self::assertSame('Úvod', $row['contextLabel']);
		self::assertSame('Auditova Jana (B-901)', $row['entityLabel']);
		self::assertSame('babysitter', $row['entityLinkType']);
		self::assertSame($babysitterId, $row['entityLinkId']);
		self::assertSame('Upravené', $row['actionLabel']);
		self::assertSame('bg-warning text-dark', $row['actionClass']);
	}

	public function testFindRowsAppliesUserDateSectionStatusEntityAndQueryFilters(): void
	{
		$repository = $this->getContainer()->getByType(ChangeLogRepository::class);
		$fixtures = $this->seedChangeLogRows();

		self::assertSame([
			'created' => 'Vytvorené',
			'updated' => 'Upravené',
			'deleted' => 'Vymazané',
		], $repository->getActionOptions());
		self::assertArrayHasKey('babysitter', $repository->getSectionOptions());

		$this->assertChangeContexts($repository, ['user' => (string) $fixtures['users']['admin']], ['documents.babysitter', 'babysitter.main']);
		$this->assertChangeContexts($repository, ['dateFrom' => '02.06.2026', 'dateTo' => '02.06.2026'], ['family.info']);
		$this->assertChangeContexts($repository, ['section' => 'babysitter'], ['documents.babysitter', 'babysitter.main']);
		$this->assertChangeContexts($repository, ['status' => 'updated'], ['babysitter.main']);
		$this->assertChangeContexts($repository, ['status' => 'created'], ['family.info']);
		$this->assertChangeContexts($repository, ['status' => 'deleted'], ['documents.babysitter']);
		$this->assertChangeContexts($repository, ['entity' => 'Auditova'], ['documents.babysitter', 'babysitter.main']);
		$this->assertChangeContexts($repository, ['q' => 'Rodina nova'], ['family.info']);

		self::assertSame([
			$fixtures['users']['admin'] => 'Admin Audit',
			$fixtures['users']['dealer'] => 'Dealer Test',
		], $repository->findUserOptions());
	}

	public function testFindRowsResolvesDocumentOwnerEntityLabelAndCreatedAction(): void
	{
		$repository = $this->getContainer()->getByType(ChangeLogRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Admin',
			UserTableMap::COL_SECOND_NAME => 'Documents',
			UserTableMap::COL_EMAIL => 'documents.admin@example.test',
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-902',
			OpatrovatelkaTableMap::COL_NAME => 'Eva',
			OpatrovatelkaTableMap::COL_SURNAME => 'Dokumentova',
		]);
		$fileId = TestDatabase::createFile([
			FileTableMap::COL_DIR => 'babysitters/' . $babysitterId,
			FileTableMap::COL_NAME => 'profil.pdf',
			FileTableMap::COL_USER => $userId,
			FileTableMap::COL_TYPE => 'pdf',
		]);

		TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'documents.babysitter',
			ChangeLogTableMap::COL_ENTITY_TABLE => FileTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $fileId,
			ChangeLogTableMap::COL_FIELD_NAME => 'document',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Dokument',
			ChangeLogTableMap::COL_COLUMN_NAME => null,
			ChangeLogTableMap::COL_VALUE_TYPE => 'file',
			ChangeLogTableMap::COL_OLD_VALUE_ID => null,
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => null,
			ChangeLogTableMap::COL_NEW_VALUE_ID => (string) $fileId,
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'profil.pdf',
			ChangeLogTableMap::COL_USER_ID => $userId,
			ChangeLogTableMap::COL_METADATA => $this->metadata(['action' => 'uploaded']),
			ChangeLogTableMap::COL_CREATED_AT => '2026-06-05 14:00:00',
		]);

		$pageCount = 0;
		$rows = $repository->findRows(1, 10, $pageCount);

		self::assertSame(1, $pageCount);
		self::assertCount(1, $rows);

		$row = $rows[0];
		self::assertSame('documents.babysitter', $row['context']);
		self::assertSame('Opatrovateľky / pracovníci', $row['sectionLabel']);
		self::assertSame('Dokumenty', $row['contextLabel']);
		self::assertSame('Súbor', $row['valueTypeLabel']);
		self::assertSame('Dokumentova Eva (B-902)', $row['entityLabel']);
		self::assertSame('Súbor: profil.pdf', $row['entityNote']);
		self::assertSame('babysitter', $row['entityLinkType']);
		self::assertSame($babysitterId, $row['entityLinkId']);
		self::assertSame('Vytvorené', $row['actionLabel']);
		self::assertSame('bg-success', $row['actionClass']);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @param list<string> $expectedContexts
	 */
	private function assertChangeContexts(ChangeLogRepository $repository, array $filters, array $expectedContexts): void
	{
		$pageCount = 0;
		$rows = $repository->findRows(1, 10, $pageCount, $filters);

		self::assertSame($expectedContexts, array_column($rows, 'context'));
		self::assertSame(1, $pageCount);
	}

	/**
	 * @return array{
	 *     users: array{admin:int, dealer:int},
	 *     entities: array{babysitter:int, family:int, file:int},
	 *     logs: array{updated:int, created:int, deleted:int}
	 * }
	 */
	private function seedChangeLogRows(): array
	{
		$adminId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Admin',
			UserTableMap::COL_SECOND_NAME => 'Audit',
			UserTableMap::COL_EMAIL => 'admin.audit@example.test',
		]);
		$dealerId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Dealer',
			UserTableMap::COL_SECOND_NAME => 'Test',
			UserTableMap::COL_EMAIL => 'dealer.test@example.test',
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-903',
			OpatrovatelkaTableMap::COL_NAME => 'Marta',
			OpatrovatelkaTableMap::COL_SURNAME => 'Auditova',
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-903',
			FamilyTableMap::COL_NAME => 'Eva',
			FamilyTableMap::COL_SURNAME => 'Rodina',
		]);
		$fileId = TestDatabase::createFile([
			FileTableMap::COL_DIR => 'babysitters/' . $babysitterId,
			FileTableMap::COL_NAME => 'zmluva-auditova.pdf',
			FileTableMap::COL_USER => $adminId,
			FileTableMap::COL_TYPE => 'pdf',
		]);

		$updatedId = TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'babysitter.main',
			ChangeLogTableMap::COL_ENTITY_TABLE => OpatrovatelkaTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $babysitterId,
			ChangeLogTableMap::COL_FIELD_NAME => 'notice',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Poznámka',
			ChangeLogTableMap::COL_COLUMN_NAME => 'notice',
			ChangeLogTableMap::COL_VALUE_TYPE => 'text',
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => 'Stara hodnota',
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'Nova hodnota',
			ChangeLogTableMap::COL_USER_ID => $adminId,
			ChangeLogTableMap::COL_METADATA => null,
			ChangeLogTableMap::COL_CREATED_AT => '2026-06-01 10:00:00',
		]);
		$createdId = TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'family.info',
			ChangeLogTableMap::COL_ENTITY_TABLE => FamilyTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $familyId,
			ChangeLogTableMap::COL_FIELD_NAME => 'person_phone',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Kontakt',
			ChangeLogTableMap::COL_COLUMN_NAME => 'person_phone',
			ChangeLogTableMap::COL_VALUE_TYPE => 'text',
			ChangeLogTableMap::COL_OLD_VALUE_ID => null,
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => null,
			ChangeLogTableMap::COL_NEW_VALUE_ID => 'phone',
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'Rodina nova hodnota',
			ChangeLogTableMap::COL_USER_ID => $dealerId,
			ChangeLogTableMap::COL_METADATA => $this->metadata(['action' => 'created']),
			ChangeLogTableMap::COL_CREATED_AT => '2026-06-02 12:00:00',
		]);
		$deletedId = TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'documents.babysitter',
			ChangeLogTableMap::COL_ENTITY_TABLE => FileTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $fileId,
			ChangeLogTableMap::COL_FIELD_NAME => 'document',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Dokument',
			ChangeLogTableMap::COL_COLUMN_NAME => null,
			ChangeLogTableMap::COL_VALUE_TYPE => 'file',
			ChangeLogTableMap::COL_OLD_VALUE_ID => (string) $fileId,
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => 'zmluva-auditova.pdf',
			ChangeLogTableMap::COL_NEW_VALUE_ID => null,
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => null,
			ChangeLogTableMap::COL_USER_ID => $adminId,
			ChangeLogTableMap::COL_METADATA => $this->metadata(['action' => 'deleted']),
			ChangeLogTableMap::COL_CREATED_AT => '2026-06-03 13:00:00',
		]);

		return [
			'users' => [
				'admin' => $adminId,
				'dealer' => $dealerId,
			],
			'entities' => [
				'babysitter' => $babysitterId,
				'family' => $familyId,
				'file' => $fileId,
			],
			'logs' => [
				'updated' => $updatedId,
				'created' => $createdId,
				'deleted' => $deletedId,
			],
		];
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	private function metadata(array $metadata): string
	{
		$json = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		self::assertIsString($json);

		return $json;
	}
}
