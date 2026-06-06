<?php declare(strict_types=1);

namespace Tests\Snapshot;

use App\Model\Service\Autosave\AutosaveFieldUpdateService;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use Nette\Utils\Json;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\FakePostRequest;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\SnapshotAssertions;

/**
 * Captures the stable shape of change-log rows produced by representative autosave edits
 * (text, select, date, junction). Volatile columns (id, entity_id, user_id, timestamp) are
 * stripped so only the contract-relevant payload is snapshotted.
 */
final class ChangeLogPayloadSnapshotTest extends DatabaseTestCase
{
	use SnapshotAssertions;

	public function testAutosaveChangeLogPayloadShapeMatchesBaseline(): void
	{
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_TYPE => 1,
		]);

		$service = $this->getContainer()->getByType(AutosaveFieldUpdateService::class);

		$service->tryHandleRequest(new FakePostRequest([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		]));
		$service->tryHandleRequest(new FakePostRequest([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.main',
			'__autosave_field' => 'type',
			'__autosave_value' => '2',
		]));
		$service->tryHandleRequest(new FakePostRequest([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'birthday',
			'__autosave_value' => '12.04.1985',
		]));
		$service->tryHandleRequest(new FakePostRequest([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.profile',
			'__autosave_field' => 'diseaseIds',
			'__autosave_value' => '1',
			'__autosave_checked' => '1',
			'__autosave_item_id' => '1',
		]));

		$this->assertMatchesSnapshot('autosave-changelog-payload', $this->dumpChangeLog());
	}

	private function dumpChangeLog(): string
	{
		$rows = [];
		foreach ($this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->order(ChangeLogTableMap::COL_ID)->fetchAll() as $row) {
			$rows[] = [
				'context' => $row->{ChangeLogTableMap::COL_CONTEXT},
				'entity_table' => $row->{ChangeLogTableMap::COL_ENTITY_TABLE},
				'field_name' => $row->{ChangeLogTableMap::COL_FIELD_NAME},
				'field_label' => $row->{ChangeLogTableMap::COL_FIELD_LABEL},
				'column_name' => $row->{ChangeLogTableMap::COL_COLUMN_NAME},
				'value_type' => $row->{ChangeLogTableMap::COL_VALUE_TYPE},
				'old_value_id' => $row->{ChangeLogTableMap::COL_OLD_VALUE_ID},
				'old_value_label' => $row->{ChangeLogTableMap::COL_OLD_VALUE_LABEL},
				'new_value_id' => $row->{ChangeLogTableMap::COL_NEW_VALUE_ID},
				'new_value_label' => $row->{ChangeLogTableMap::COL_NEW_VALUE_LABEL},
				'metadata' => $row->{ChangeLogTableMap::COL_METADATA},
			];
		}

		return Json::encode($rows, pretty: true) . "\n";
	}
}
