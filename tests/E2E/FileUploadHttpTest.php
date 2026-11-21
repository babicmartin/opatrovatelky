<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Repository\BabysitterVideoRepository;
use App\Model\Table\BabysitterVideoTableMap;
use App\Model\Table\FileTableMap;
use Dom\HTMLDocument;
use GuzzleHttp\Psr7\Utils;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\StreamInterface;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class FileUploadHttpTest extends DatabaseTestCase
{
	private TestWebClient $web;
	private StorageDirProvider $storage;
	private int $userId;
	private string $directory;


	protected function setUp(): void
	{
		parent::setUp();
		$this->storage = $this->getContainer()->getByType(StorageDirProvider::class);
		$this->userId = TestDatabase::createUser();
		$this->web = new TestWebClient(uploadLimitMiB: 210);
		self::assertSame(303, $this->web->login('test.user@example.test', 'secret123!')->getStatusCode());
	}


	protected function tearDown(): void
	{
		if (isset($this->web)) {
			$this->web->stop();
		}
		if (isset($this->directory)) {
			foreach (new \DirectoryIterator($this->directory) as $file) {
				if (!$file->isDot()) {
					unlink($file->getPathname());
				}
			}
			rmdir($this->directory);
		}
		parent::tearDown();
	}


	/** @return array<string, array{string, string, string, string}> */
	public static function documentForms(): array
	{
		return [
			'babysitter' => ['babysitter', '?tab=documents', 'babysitterDocuments', 'babysitters'],
			'family contracts' => ['family', '?tab=contracts', 'familyContractsDocuments', 'families-orders'],
			'family orders' => ['family', '?tab=orders', 'familyOrdersDocuments', 'families-contracts'],
			'partner' => ['partner', '', 'partnerDocuments', 'partners'],
			'agency' => ['agency', '', 'agencyDocuments', 'agencies'],
			'turnus' => ['turnus', '', 'turnusDocuments', 'turnus'],
		];
	}


	#[DataProvider('documentForms')]
	public function testRejectedDocumentsLeaveStorageAndRowsIntact(string $presenter, string $tab, string $component, string $folder): void
	{
		$owner = ['id' => random_int(1_000_000, 1_000_000_000)];
		$id = match ($presenter) {
			'babysitter' => TestDatabase::createBabysitter($owner),
			'family' => TestDatabase::createFamily($owner),
			'partner' => TestDatabase::createPartner($owner),
			'agency' => TestDatabase::createAgency($owner),
			'turnus' => TestDatabase::createTurnus($owner),
			default => self::fail('Unknown document presenter ' . $presenter),
		};
		$page = '/' . $presenter . '/update/' . $id . $tab;
		$formId = 'frm-' . $component . '-uploadForm';
		$relativeDir = $folder . '/' . $id;
		$this->createDirectory($this->storage->getPrivateDocuments(), $relativeDir);
		$pdf = "%PDF-1.4\n% Control document\n%%EOF\n";
		TestDatabase::createFile(['dir' => $relativeDir, 'name' => 'control.pdf', 'type' => 'pdf', 'user' => $this->userId]);
		file_put_contents($this->directory . '/control.pdf', $pdf);
		$rows = $this->rows(FileTableMap::TABLE_NAME);
		$files = scandir($this->directory);

		foreach ([
			['document.txt', $pdf, null, 'Povolené sú iba tieto koncovky: pdf, doc, docs, docx'],
			['document.pdf', 'Plain text content', null, 'Nepovolený typ súboru.'],
			['large.pdf', $pdf, 20 * 1024 * 1024 + 1, 'Maximálna veľkosť súboru je 20 MB.'],
		] as [$filename, $contents, $size, $error]) {
			$response = $this->web->submitUpload($page, $formId, $filename, $this->fileContents($contents, $size));
			self::assertSame(200, $response->getStatusCode(), $filename);
			$html = HTMLDocument::createFromString((string) $response->getBody(), LIBXML_NOERROR, 'UTF-8');
			$message = $html->getElementById($formId)?->querySelector('[role=alert]');
			self::assertNotNull($message, $filename);
			self::assertSame($error, trim($message->textContent ?? ''), $filename);
			self::assertSame($rows, $this->rows(FileTableMap::TABLE_NAME));
			self::assertSame($files, scandir($this->directory));
			self::assertSame($pdf, file_get_contents($this->directory . '/control.pdf'));
		}
	}


	public function testRejectedVideosReturnAjaxErrorsAndLeaveStorageAndRowsIntact(): void
	{
		$id = TestDatabase::createBabysitter(['id' => random_int(1_000_000, 1_000_000_000)]);
		$this->createDirectory($this->storage->getPrivateVideos(), 'babysitters/' . $id);
		$mp4 = file_get_contents(__DIR__ . '/../Support/fixtures/sample-video.mp4');
		self::assertIsString($mp4);
		file_put_contents($this->directory . '/control.mp4', $mp4);
		$this->getContainer()->getByType(BabysitterVideoRepository::class)->insertVideo([
			'babysitterId' => $id,
			'originalName' => 'control.mp4',
			'storedName' => 'control.mp4',
			'extension' => 'mp4',
			'mimeType' => 'video/mp4',
			'sizeBytes' => strlen($mp4),
			'durationSeconds' => 1,
			'checksumSha256' => hash('sha256', $mp4),
			'uploadedByUserId' => $this->userId,
		]);
		$rows = $this->rows(BabysitterVideoTableMap::TABLE_NAME);
		$files = scandir($this->directory);

		foreach ([
			['recording.txt', $mp4, null, 'Povolené sú iba videá typu: mp4, webm.'],
			['recording.mp4', 'Plain text content', null, 'Nepovolený typ video súboru.'],
			['large.mp4', $mp4, 200 * 1024 * 1024 + 1, 'Maximálna veľkosť videa je 200 MB.'],
		] as [$filename, $contents, $size, $error]) {
			$response = $this->web->submitUpload(
				'/babysitter/update/' . $id . '?tab=video',
				'frm-babysitterVideos-uploadForm',
				$filename,
				$this->fileContents($contents, $size),
				ajax: true,
			);
			self::assertSame(400, $response->getStatusCode(), $filename);
			self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
			self::assertSame(['success' => false, 'message' => $error], json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR));
			self::assertSame($rows, $this->rows(BabysitterVideoTableMap::TABLE_NAME));
			self::assertSame($files, scandir($this->directory));
			self::assertSame($mp4, file_get_contents($this->directory . '/control.mp4'));
		}
	}


	public function testAllowedExtensionWithGenericBinaryContentIsStored(): void
	{
		$id = TestDatabase::createBabysitter(['id' => random_int(1_000_000, 1_000_000_000)]);
		$relativeDir = 'babysitters/' . $id;
		$this->createDirectory($this->storage->getPrivateDocuments(), $relativeDir);
		$contents = str_repeat("\x00\x01\x02\x03", 32);
		self::assertSame('application/octet-stream', (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents));
		$response = $this->web->submitUpload(
			'/babysitter/update/' . $id . '?tab=documents',
			'frm-babysitterDocuments-uploadForm',
			'legacy.doc',
			Utils::streamFor($contents),
		);
		self::assertSame(303, $response->getStatusCode());
		$rows = $this->rows(FileTableMap::TABLE_NAME);
		self::assertCount(1, $rows);
		self::assertSame($relativeDir, $rows[0]['dir']);
		self::assertSame('docs', $rows[0]['type']);
		$name = $rows[0]['name'];
		self::assertIsString($name);
		self::assertMatchesRegularExpression('~^legacy-[a-zA-Z0-9]+\.doc$~D', $name);
		self::assertSame(['.', '..', $name], scandir($this->directory));
		self::assertSame($contents, file_get_contents($this->directory . '/' . $name));
	}


	private function createDirectory(string $root, string $relativeDir): void
	{
		self::assertStringStartsWith('temp/test/', $root);
		$directory = dirname(__DIR__, 2) . '/' . $root . '/' . $relativeDir;
		self::assertDirectoryDoesNotExist($directory);
		self::assertTrue(mkdir($directory, 0777, true));
		$this->directory = $directory;
	}


	/** @return array<int, array<string, mixed>> */
	private function rows(string $table): array
	{
		$query = TestDatabase::pdo()->prepare("SELECT * FROM $table ORDER BY id");
		$query->execute();
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}


	/** @param positive-int|null $size */
	private function fileContents(string $prefix, ?int $size): StreamInterface
	{
		if ($size === null) {
			return Utils::streamFor($prefix);
		}

		$file = tmpfile();
		self::assertIsResource($file);
		self::assertSame(strlen($prefix), fwrite($file, $prefix));
		self::assertTrue(ftruncate($file, $size));
		rewind($file);
		return Utils::streamFor($file);
	}
}
