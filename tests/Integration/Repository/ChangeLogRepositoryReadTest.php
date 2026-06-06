<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\ChangeLogRepository;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class ChangeLogRepositoryReadTest extends DatabaseTestCase
{
	public function testFindRowsMapsLabelsAndEntityLinks(): void
	{
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Dana',
			UserTableMap::COL_SECOND_NAME => 'Auditor',
			UserTableMap::COL_EMAIL => 'dana.auditor@example.test',
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'EntityMatch',
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-123',
		]);

		TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'babysitter.address',
			ChangeLogTableMap::COL_ENTITY_TABLE => OpatrovatelkaTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $babysitterId,
			ChangeLogTableMap::COL_FIELD_NAME => 'name',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Meno',
			ChangeLogTableMap::COL_COLUMN_NAME => OpatrovatelkaTableMap::COL_NAME,
			ChangeLogTableMap::COL_VALUE_TYPE => 'text',
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => 'Anna',
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'Eva',
			ChangeLogTableMap::COL_USER_ID => $userId,
			ChangeLogTableMap::COL_CREATED_AT => '2026-06-06 10:00:00',
		]);

		$pageCount = 0;
		$rows = $this->repository()->findRows(1, 10, $pageCount);

		self::assertSame(1, $pageCount);
		self::assertCount(1, $rows);
		self::assertSame('babysitter.address', $rows[0]['context']);
		self::assertSame('Opatrovateľky / pracovníci', $rows[0]['sectionLabel']);
		self::assertSame('Základné informácie', $rows[0]['contextLabel']);
		self::assertSame('Text', $rows[0]['valueTypeLabel']);
		self::assertSame('EntityMatch Anna (B-123)', $rows[0]['entityLabel']);
		self::assertSame('babysitter', $rows[0]['entityLinkType']);
		self::assertSame($babysitterId, $rows[0]['entityLinkId']);
		self::assertSame('Dana Auditor', $rows[0]['userName']);
		self::assertSame('Upravené', $rows[0]['actionLabel']);
		self::assertSame('bg-warning text-dark', $rows[0]['actionClass']);
	}

	public function testFindRowsFiltersBySectionStatusEntityUserDateAndSearchTerm(): void
	{
		$userId = TestDatabase::createUser();
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'FilterPerson',
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'FILTER-123',
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_NAME => 'Family',
			FamilyTableMap::COL_SURNAME => 'Other',
		]);

		TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'babysitter.address',
			ChangeLogTableMap::COL_ENTITY_TABLE => OpatrovatelkaTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $babysitterId,
			ChangeLogTableMap::COL_FIELD_NAME => 'name',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Meno',
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => 'Anna',
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'EvaFilter',
			ChangeLogTableMap::COL_USER_ID => $userId,
			ChangeLogTableMap::COL_CREATED_AT => '2026-06-06 10:00:00',
		]);
		TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'family.info',
			ChangeLogTableMap::COL_ENTITY_TABLE => FamilyTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $familyId,
			ChangeLogTableMap::COL_FIELD_NAME => 'record',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Záznam',
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => null,
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'Rodina',
			ChangeLogTableMap::COL_USER_ID => null,
			ChangeLogTableMap::COL_CREATED_AT => '2026-06-07 10:00:00',
			ChangeLogTableMap::COL_METADATA => '{"action":"created"}',
		]);

		$this->assertSingleFilteredRow(['section' => 'babysitter'], $babysitterId);
		$this->assertSingleFilteredRow(['status' => 'updated'], $babysitterId);
		$this->assertSingleFilteredRow(['entity' => 'FILTER-123'], $babysitterId);
		$this->assertSingleFilteredRow(['user' => (string) $userId], $babysitterId);
		$this->assertSingleFilteredRow(['dateFrom' => '6.6.2026', 'dateTo' => '6.6.2026'], $babysitterId);
		$this->assertSingleFilteredRow(['q' => 'EvaFilter'], $babysitterId);

		$pageCount = 0;
		$createdRows = $this->repository()->findRows(1, 10, $pageCount, ['status' => 'created']);
		self::assertCount(1, $createdRows);
		self::assertSame($familyId, $createdRows[0]['entityId']);
	}

	public function testFindUserOptionsReturnsOnlyUsersWithChangeRows(): void
	{
		$userWithChanges = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Changed',
			UserTableMap::COL_SECOND_NAME => 'User',
		]);
		TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'without.change@example.test',
		]);
		$this->repository()->logChange([
			'context' => 'babysitter.main',
			'entityTable' => OpatrovatelkaTableMap::TABLE_NAME,
			'entityId' => 1,
			'fieldName' => 'notice',
			'fieldLabel' => 'Poznámka',
			'columnName' => 'notice',
			'valueType' => 'text',
			'oldValueId' => null,
			'oldValueLabel' => 'old',
			'newValueId' => null,
			'newValueLabel' => 'new',
			'userId' => $userWithChanges,
			'metadata' => null,
		]);

		self::assertSame([(string) $userWithChanges => 'Changed User'], $this->repository()->findUserOptions());
	}

	/**
	 * @param array<string, mixed> $filters
	 */
	private function assertSingleFilteredRow(array $filters, int $expectedEntityId): void
	{
		$pageCount = 0;
		$rows = $this->repository()->findRows(1, 10, $pageCount, $filters);

		self::assertSame(1, $pageCount);
		self::assertCount(1, $rows);
		self::assertSame($expectedEntityId, $rows[0]['entityId']);
	}

	private function repository(): ChangeLogRepository
	{
		return $this->getContainer()->getByType(ChangeLogRepository::class);
	}
}
