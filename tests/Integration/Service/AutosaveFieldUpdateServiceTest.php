<?php declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Model\Service\Autosave\AutosaveFieldUpdateService;
use App\Model\Table\BabysitterDiseaseTableMap;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\TurnusTableMap;
use Nette\Http\IRequest;
use Nette\Http\UrlScript;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class AutosaveFieldUpdateServiceTest extends DatabaseTestCase
{
	public function testTextFieldUpdateChangesSingleColumnAndWritesAudit(): void
	{
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Stable',
		]);

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		]));

		self::assertTrue($result);

		$babysitter = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId);
		self::assertNotNull($babysitter);
		self::assertSame('Eva', $babysitter->{OpatrovatelkaTableMap::COL_NAME});
		self::assertSame('Stable', $babysitter->{OpatrovatelkaTableMap::COL_SURNAME});

		$change = $this->singleChange();
		self::assertSame('babysitter.address', $change->{ChangeLogTableMap::COL_CONTEXT});
		self::assertSame(OpatrovatelkaTableMap::TABLE_NAME, $change->{ChangeLogTableMap::COL_ENTITY_TABLE});
		self::assertSame($babysitterId, (int) $change->{ChangeLogTableMap::COL_ENTITY_ID});
		self::assertSame('name', $change->{ChangeLogTableMap::COL_FIELD_NAME});
		self::assertSame('Meno', $change->{ChangeLogTableMap::COL_FIELD_LABEL});
		self::assertSame('Anna', $change->{ChangeLogTableMap::COL_OLD_VALUE_LABEL});
		self::assertSame('Eva', $change->{ChangeLogTableMap::COL_NEW_VALUE_LABEL});
	}

	public function testSameValueDoesNotWriteAudit(): void
	{
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
		]);

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Anna',
		]));

		self::assertTrue($result);
		self::assertSame(0, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
	}

	public function testSelectFieldStoresIdsAndReadableLabels(): void
	{
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_TYPE => 1,
		]);

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.main',
			'__autosave_field' => 'type',
			'__autosave_value' => '2',
		]));

		self::assertTrue($result);
		self::assertSame(2, (int) $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId)?->{OpatrovatelkaTableMap::COL_TYPE});

		$change = $this->singleChange();
		self::assertSame('1', $change->{ChangeLogTableMap::COL_OLD_VALUE_ID});
		self::assertSame('Opatrovateľka', $change->{ChangeLogTableMap::COL_OLD_VALUE_LABEL});
		self::assertSame('2', $change->{ChangeLogTableMap::COL_NEW_VALUE_ID});
		self::assertSame('Pracovník', $change->{ChangeLogTableMap::COL_NEW_VALUE_LABEL});
	}

	public function testInvalidDateIsRejectedWithoutChangingDatabase(): void
	{
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_DATE_START => '2026-06-01',
		]);

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $familyId,
			'__autosave_context' => 'family.info',
			'__autosave_field' => 'dateStart',
			'__autosave_value' => '31.2.2026',
		]));

		self::assertFalse($result);
		self::assertSame('2026-06-01', $this->getDatabase()->table(FamilyTableMap::TABLE_NAME)->get($familyId)?->{FamilyTableMap::COL_DATE_START}->format('Y-m-d'));
		self::assertSame(0, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
	}

	public function testFloatFieldAcceptsSlovakDecimalInput(): void
	{
		$turnusId = TestDatabase::createTurnus([
			TurnusTableMap::COL_FEE => 10.5,
			TurnusTableMap::COL_NOTICE => 'keep',
		]);

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $turnusId,
			'__autosave_context' => 'turnus.update',
			'__autosave_field' => 'fee',
			'__autosave_value' => '1 234,50',
		]));

		self::assertTrue($result);

		$turnus = $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->get($turnusId);
		self::assertSame(1234.5, (float) $turnus?->{TurnusTableMap::COL_FEE});
		self::assertSame('keep', $turnus?->{TurnusTableMap::COL_NOTICE});
		self::assertSame('Honorár DLV', $this->singleChange()->{ChangeLogTableMap::COL_FIELD_LABEL});
	}

	public function testCheckboxFieldWritesBooleanAuditLabels(): void
	{
		$registryId = TestDatabase::insert(MissingRegistryTableMap::TABLE_NAME, [
			MissingRegistryTableMap::COL_USER_ID => 1,
			MissingRegistryTableMap::COL_DATE_FROM => '2026-06-01',
			MissingRegistryTableMap::COL_DATE_TO => '2026-06-02',
			MissingRegistryTableMap::COL_TYPE_PN => 0,
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $registryId,
			'__autosave_context' => 'missingRegistry.row',
			'__autosave_field' => 'typePn',
			'__autosave_value' => '1',
			'__autosave_checked' => '1',
		]));

		self::assertTrue($result);
		self::assertSame(1, (int) $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->get($registryId)?->{MissingRegistryTableMap::COL_TYPE_PN});

		$change = $this->singleChange();
		self::assertSame('0', $change->{ChangeLogTableMap::COL_OLD_VALUE_ID});
		self::assertSame('Nie', $change->{ChangeLogTableMap::COL_OLD_VALUE_LABEL});
		self::assertSame('1', $change->{ChangeLogTableMap::COL_NEW_VALUE_ID});
		self::assertSame('Áno', $change->{ChangeLogTableMap::COL_NEW_VALUE_LABEL});
	}

	public function testJunctionCheckboxAddsOnlySelectedItemAndWritesMetadata(): void
	{
		$babysitterId = TestDatabase::createBabysitter();

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.profile',
			'__autosave_field' => 'diseaseIds',
			'__autosave_value' => '1',
			'__autosave_checked' => '1',
			'__autosave_item_id' => '1',
		]));

		self::assertTrue($result);
		self::assertNotNull($this->getDatabase()->table(BabysitterDiseaseTableMap::TABLE_NAME)
			->where(BabysitterDiseaseTableMap::COL_BABYSITTER_ID, $babysitterId)
			->where(BabysitterDiseaseTableMap::COL_DISEASE_ID, 1)
			->fetch());

		$change = $this->singleChange();
		self::assertSame(BabysitterDiseaseTableMap::TABLE_NAME, $change->{ChangeLogTableMap::COL_ENTITY_TABLE});
		self::assertSame('diseaseIds', $change->{ChangeLogTableMap::COL_FIELD_NAME});
		self::assertSame('1', $change->{ChangeLogTableMap::COL_NEW_VALUE_ID});
		self::assertSame('Demencia', $change->{ChangeLogTableMap::COL_NEW_VALUE_LABEL});
		self::assertJsonStringEqualsJsonString('{"action":"added","item_id":1,"item_label":"Demencia"}', (string) $change->{ChangeLogTableMap::COL_METADATA});
	}

	public function testDocumentContextUpdatesFileRow(): void
	{
		$fileId = TestDatabase::createFile([
			FileTableMap::COL_NOTICE => 'old note',
		]);

		$result = $this->service()->tryHandleRequest($this->request([
			'id' => (string) $fileId,
			'__autosave_context' => 'documents.babysitter',
			'__autosave_field' => 'notice',
			'__autosave_value' => 'new note',
		]));

		self::assertTrue($result);
		self::assertSame('new note', $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($fileId)?->{FileTableMap::COL_NOTICE});
		self::assertSame('documents.babysitter', $this->singleChange()->{ChangeLogTableMap::COL_CONTEXT});
	}

	public function testUnknownContextReturnsFalse(): void
	{
		$result = $this->service()->tryHandleRequest($this->request([
			'id' => '1',
			'__autosave_context' => 'unknown.context',
			'__autosave_field' => 'name',
			'__autosave_value' => 'value',
		]));

		self::assertFalse($result);
		self::assertSame(0, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
	}

	private function service(): AutosaveFieldUpdateService
	{
		return $this->getContainer()->getByType(AutosaveFieldUpdateService::class);
	}

	/**
	 * @param array<string, mixed> $post
	 */
	private function request(array $post): IRequest
	{
		return new class($post) implements IRequest {
			/**
			 * @param array<string, mixed> $post
			 */
			public function __construct(private readonly array $post)
			{
			}

			public function getUrl(): UrlScript
			{
				return new UrlScript('https://example.test/');
			}

			public function getQuery(?string $key = null): mixed
			{
				return $key === null ? [] : null;
			}

			public function getPost(?string $key = null): mixed
			{
				return $key === null ? $this->post : ($this->post[$key] ?? null);
			}

			/**
			 * @return array<string, mixed>|null
			 */
			public function getFile(string $key): ?array
			{
				return null;
			}

			/**
			 * @return array<string, mixed>
			 */
			public function getFiles(): array
			{
				return [];
			}

			public function getCookie(string $key): mixed
			{
				return null;
			}

			/**
			 * @return array<string, string>
			 */
			public function getCookies(): array
			{
				return [];
			}

			public function getMethod(): string
			{
				return self::Post;
			}

			public function isMethod(string $method): bool
			{
				return strcasecmp($method, self::Post) === 0;
			}

			public function getHeader(string $header): ?string
			{
				return null;
			}

			/**
			 * @return array<string, string>
			 */
			public function getHeaders(): array
			{
				return [];
			}

			public function isSecured(): bool
			{
				return true;
			}

			public function isAjax(): bool
			{
				return true;
			}

			public function getRemoteAddress(): string
			{
				return '127.0.0.1';
			}

			public function getRemoteHost(): string
			{
				return 'localhost';
			}

			public function getRawBody(): ?string
			{
				return null;
			}
		};
	}

	private function singleChange(): mixed
	{
		$rows = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->fetchAll();

		self::assertCount(1, $rows);

		return reset($rows);
	}
}
