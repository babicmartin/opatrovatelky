<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\Table\FileTableMap;
use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

/**
 * The document row form carries the document ID in a hidden field.
 * The form must save the document it was built for, never the one named by the request.
 */
final class DocumentFormHttpTest extends DatabaseTestCase
{
	private TestWebClient $web;


	protected function setUp(): void
	{
		parent::setUp();
		TestDatabase::createUser();
		$this->web = new TestWebClient();
		self::assertSame(303, $this->web->login('test.user@example.test', 'secret123!')->getStatusCode());
	}


	protected function tearDown(): void
	{
		if (isset($this->web)) {
			$this->web->stop();
		}
		parent::tearDown();
	}


	#[DataProviderExternal(FileUploadHttpTest::class, 'documentForms')]
	public function testForgedHiddenIdDoesNotReachAnotherDocument(string $presenter, string $tab, string $component, string $folder): void
	{
		$owner = ['id' => random_int(1_000_000, 1_000_000_000)];
		$ownerId = match ($presenter) {
			'babysitter' => TestDatabase::createBabysitter($owner),
			'family' => TestDatabase::createFamily($owner),
			'partner' => TestDatabase::createPartner($owner),
			'agency' => TestDatabase::createAgency($owner),
			'turnus' => TestDatabase::createTurnus($owner),
			default => self::fail('Unknown document presenter ' . $presenter),
		};
		$ownId = TestDatabase::createFile([
			FileTableMap::COL_DIR => $folder . '/' . $ownerId,
			FileTableMap::COL_NAME => 'own.pdf',
			FileTableMap::COL_NOTICE => 'own note',
		]);
		// A document of another record and, for most cases, of another module.
		$foreignFolder = $folder === 'babysitters' ? 'partners' : 'babysitters';
		$foreignId = TestDatabase::createFile([
			FileTableMap::COL_DIR => $foreignFolder . '/' . ($ownerId + 1),
			FileTableMap::COL_NAME => 'foreign.pdf',
			FileTableMap::COL_NOTICE => 'foreign note',
		]);
		$foreignBefore = $this->fileRow($foreignId);

		$response = $this->web->submitForm(
			'/' . $presenter . '/update/' . $ownerId . $tab,
			'frm-' . $component . '-documentForm-' . $ownId,
			// A browser always sends the status select; 0 is its empty option.
			['id' => (string) $foreignId, 'notice' => 'tampered', 'status' => '0'],
		);

		self::assertSame(303, $response->getStatusCode());
		self::assertSame($foreignBefore, $this->fileRow($foreignId));
		self::assertSame('tampered', $this->fileRow($ownId)[FileTableMap::COL_NOTICE]);
	}


	/** @return array<string, mixed> */
	private function fileRow(int $id): array
	{
		$statement = TestDatabase::pdo()->prepare('SELECT * FROM `' . FileTableMap::TABLE_NAME . '` WHERE `' . FileTableMap::COL_ID . '` = ?');
		$statement->execute([$id]);
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		self::assertIsArray($row);

		return $row;
	}
}
