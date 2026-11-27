<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\UserTableMap;
use Dom\HTMLDocument;
use GuzzleHttp\Psr7\Utils;
use Nette\Utils\FileSystem;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

/**
 * What a signed-in employee can do to the documents of a record by editing a request:
 * delete by a pasted address, change a document without a trace, forge a field the page does not offer,
 * upload to a page that has no record, and put markup of their own into the page of a colleague.
 */
final class DocumentSecurityHttpTest extends DatabaseTestCase
{
	private TestWebClient $web;
	private int $userId;

	/** @var list<string> directories an unfixed upload may leave behind */
	private array $orphanDirectories = [];


	protected function setUp(): void
	{
		parent::setUp();
		$this->userId = TestDatabase::createUser();
		$this->web = new TestWebClient();
		self::assertSame(303, $this->web->login('test.user@example.test', 'secret123!')->getStatusCode());
	}


	protected function tearDown(): void
	{
		if (isset($this->web)) {
			$this->web->stop();
		}
		foreach ($this->orphanDirectories as $directory) {
			FileSystem::delete($directory);
		}
		parent::tearDown();
	}


	#[DataProviderExternal(FileUploadHttpTest::class, 'documentForms')]
	public function testDocumentIsDeletedOnlyByItsOwnForm(string $presenter, string $tab, string $component, string $folder): void
	{
		[$page, $ownerId] = $this->createOwner($presenter, $tab);
		$ownId = $this->createDocument($folder, $ownerId, 'own.pdf');
		$foreignId = $this->createDocument($folder === 'babysitters' ? 'partners' : 'babysitters', $ownerId + 1, 'foreign.pdf');

		// An address that deletes can be pasted, linked or prefetched; deleting needs a submitted form.
		$this->web->get($page . ($tab === '' ? '?' : '&') . 'do=' . $component . '-delete&' . $component . '-id=' . $ownId);
		self::assertSame(1, $this->fileRow($ownId)[FileTableMap::COL_ACTIVE]);

		$forged = $this->web->post($page, ['_do' => $component . '-deleteForm-' . $foreignId . '-submit', 'delete' => '1']);
		self::assertSame(404, $forged->getStatusCode());
		self::assertSame(1, $this->fileRow($foreignId)[FileTableMap::COL_ACTIVE]);
		self::assertSame(0, $this->countChanges());

		$response = $this->web->submitForm($page, 'frm-' . $component . '-deleteForm-' . $ownId);
		self::assertSame(303, $response->getStatusCode());
		self::assertSame(0, $this->fileRow($ownId)[FileTableMap::COL_ACTIVE]);
		self::assertSame(1, $this->fileRow($foreignId)[FileTableMap::COL_ACTIVE]);
		self::assertSame(1, $this->countChanges());
	}


	#[DataProviderExternal(FileUploadHttpTest::class, 'documentForms')]
	public function testWholeFormSubmitLeavesATraceAndWritesOnlyOfferedFields(string $presenter, string $tab, string $component, string $folder): void
	{
		[$page, $ownerId] = $this->createOwner($presenter, $tab);
		$documentId = $this->createDocument($folder, $ownerId, 'own.pdf');
		$formId = 'frm-' . $component . '-documentForm-' . $documentId;
		// The page of a turnus offers no status; the other pages offer a select whose empty option is 0.
		$fields = ['notice' => 'changed by hand', 'validFrom' => '01.02.2026', 'validTo' => '', 'status' => $presenter === 'turnus' ? '7' : '0'];

		self::assertSame(303, $this->web->submitForm($page, $formId, $fields)->getStatusCode());

		$row = $this->fileRow($documentId);
		self::assertSame('changed by hand', $row[FileTableMap::COL_NOTICE]);
		self::assertSame(0, $row[FileTableMap::COL_STATUS]);
		$changes = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->fetchAll();
		self::assertCount(1, $changes);
		$change = reset($changes);
		self::assertNotFalse($change);
		self::assertSame('documents.' . $presenter, $change->{ChangeLogTableMap::COL_CONTEXT});
		self::assertSame($documentId, $change->{ChangeLogTableMap::COL_ENTITY_ID});
		self::assertSame($this->userId, $change->{ChangeLogTableMap::COL_USER_ID});

		// Saving the same values again changes nothing and logs nothing.
		self::assertSame(303, $this->web->submitForm($page, $formId, $fields)->getStatusCode());
		self::assertSame(1, $this->countChanges());
	}


	/** @return array<string, array{string, string, string}> */
	public static function pagesWithoutARecord(): array
	{
		return [
			'babysitter' => ['babysitter', 'babysitterDocuments', 'babysitters'],
			'family contracts' => ['family', 'familyContractsDocuments', 'families-orders'],
			'family orders' => ['family', 'familyOrdersDocuments', 'families-contracts'],
			'partner' => ['partner', 'partnerDocuments', 'partners'],
			'agency' => ['agency', 'agencyDocuments', 'agencies'],
			'turnus' => ['turnus', 'turnusDocuments', 'turnus'],
		];
	}


	#[DataProvider('pagesWithoutARecord')]
	public function testUploadToAPageWithoutARecordIsRefused(string $presenter, string $component, string $folder): void
	{
		$container = $this->getContainer();
		$storage = $container->getByType(StorageDirProvider::class)->getPrivateDocuments();
		self::assertStringStartsWith('temp/test/', $storage);
		$orphanDirectory = $container->getByType(DirectoryProvider::class)->getRootDir() . '/' . $storage . '/' . $folder . '/0';
		self::assertDirectoryDoesNotExist($orphanDirectory);
		$this->orphanDirectories[] = $orphanDirectory;

		// The list page creates no record context, yet the component of the detail page answers there too.
		$page = '/' . $presenter;
		$list = $this->web->get($page);
		self::assertSame(200, $list->getStatusCode());
		$token = HTMLDocument::createFromString((string) $list->getBody(), LIBXML_NOERROR, 'UTF-8')
			->querySelector('input[name="_token_"]')
			?->getAttribute('value');
		self::assertIsString($token);

		$response = $this->web->postUpload(
			$page,
			['_do' => $component . '-uploadForm-submit', '_token_' => $token, 'upload' => ''],
			'fileAll',
			'orphan.pdf',
			Utils::streamFor("%PDF-1.4\n% Orphan document\n%%EOF\n"),
		);

		self::assertSame(404, $response->getStatusCode());
		self::assertSame(0, $this->getDatabase()->table(FileTableMap::TABLE_NAME)->count('*'));
		self::assertDirectoryDoesNotExist($orphanDirectory);
		self::assertSame(0, $this->countChanges());
	}


	#[DataProviderExternal(FileUploadHttpTest::class, 'documentForms')]
	public function testColourOfTheUploaderIsPrintedOnlyWhenItIsAColour(string $presenter, string $tab, string $component, string $folder): void
	{
		[$page, $ownerId] = $this->createOwner($presenter, $tab);
		// Fits the ten characters of the column and is not a colour.
		$tamperingUserId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'tampering.user@example.test',
			UserTableMap::COL_ACRONYM => 'XX',
			UserTableMap::COL_COLOR => 'red;zoom:9',
		]);
		$this->createDocument($folder, $ownerId, 'own.pdf');
		$this->createDocument($folder, $ownerId, 'tampered.pdf', $tamperingUserId);

		$response = $this->web->get($page);
		self::assertSame(200, $response->getStatusCode());
		$html = (string) $response->getBody();

		self::assertStringNotContainsString('zoom:9', $html);
		$badges = [];
		foreach (HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8')->querySelectorAll('span.badge') as $badge) {
			$badges[trim((string) $badge->textContent)] = $badge->getAttribute('style');
		}
		self::assertSame('background-color:#8A2062', $badges['TU'] ?? null);
		self::assertArrayHasKey('XX', $badges);
		self::assertNull($badges['XX']);
	}


	/** @return array{string, int} address of the detail page and ID of its record */
	private function createOwner(string $presenter, string $tab): array
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

		return ['/' . $presenter . '/update/' . $ownerId . $tab, $ownerId];
	}


	private function createDocument(string $folder, int $ownerId, string $name, ?int $userId = null): int
	{
		return TestDatabase::createFile([
			FileTableMap::COL_DIR => $folder . '/' . $ownerId,
			FileTableMap::COL_NAME => $name,
			FileTableMap::COL_TYPE => 'pdf',
			FileTableMap::COL_USER => $userId ?? $this->userId,
			FileTableMap::COL_STATUS => 0,
		]);
	}


	private function countChanges(): int
	{
		return $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*');
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
