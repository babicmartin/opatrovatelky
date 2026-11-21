<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Repository\BabysitterVideoRepository;
use App\Model\Table\BabysitterVideoTableMap;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\FileTableMap;
use Dom\HTMLDocument;
use GuzzleHttp\Psr7\Message;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Fixture\BabysitterPrivateFiles;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class BabysitterPrivateFilesHttpTest extends DatabaseTestCase
{
	private TestWebClient $web;
	private BabysitterPrivateFiles $first;
	private BabysitterPrivateFiles $second;
	private int $userId;

	/** @var list<string> */
	private array $paths = [];


	protected function setUp(): void
	{
		parent::setUp();
		$container = $this->getContainer();
		$root = $container->getByType(DirectoryProvider::class)->getRootDir();
		$storage = $container->getByType(StorageDirProvider::class);
		self::assertStringStartsWith('temp/test/', $storage->getPrivateDocuments());
		self::assertStringStartsWith('temp/test/', $storage->getPrivateVideos());
		$sample = file_get_contents(__DIR__ . '/../Support/fixtures/sample-video.mp4');
		self::assertIsString($sample);
		$this->userId = TestDatabase::createUser();
		$createCard = fn(string $label): BabysitterPrivateFiles => new BabysitterPrivateFiles(
			$label,
			$root . '/' . $storage->getPrivateDocuments(),
			$root . '/' . $storage->getPrivateVideos(),
			$sample,
			$this->userId,
			$container->getByType(BabysitterVideoRepository::class),
		);
		$this->first = $createCard('alpha');
		$this->second = $createCard('bravo');
		foreach ([$this->first, $this->second] as $card) {
			foreach ([$card->documentPath => $card->documentContents, $card->videoPath => $card->videoContents] as $path => $contents) {
				self::assertTrue(mkdir(dirname($path), recursive: true));
				$this->paths[] = $path;
				self::assertSame(strlen($contents), file_put_contents($path, $contents));
			}
		}
		$this->web = new TestWebClient();
		self::assertSame(303, $this->web->login('test.user@example.test', 'secret123!')->getStatusCode());
	}


	protected function tearDown(): void
	{
		if (isset($this->web)) {
			$this->web->stop();
		}
		foreach ($this->paths as $path) {
			if (is_file($path)) {
				unlink($path);
			}
			rmdir(dirname($path));
		}
		parent::tearDown();
	}


	public function testCardsListAndServeTheirOwnFiles(): void
	{
		foreach ([[$this->first, $this->second], [$this->second, $this->first]] as [$card, $other]) {
			$documents = $this->openTab($card, 'documents');
			self::assertStringContainsString($card->notice, $documents);
			self::assertStringNotContainsString($other->notice, $documents);
			$download = $this->web->get($this->getLink($documents, 'babysitterDocuments-download'));
			$this->assertFileResponse($download, 'attachment', basename($card->documentPath), $card->documentContents);

			$videos = $this->openTab($card, 'video');
			self::assertStringContainsString($card->videoName, $videos);
			self::assertStringNotContainsString($other->videoName, $videos);
			$download = $this->web->get($this->getLink($videos, 'babysitterVideos-download'));
			$this->assertFileResponse($download, 'attachment', $card->videoName, $card->videoContents);
			$playback = $this->web->get($this->getLink($videos, 'babysitterVideos-play'));
			$this->assertFileResponse($playback, 'inline', $card->videoName, $card->videoContents);
		}
	}


	/** @return array<string, array{string, string}> */
	public static function privateFileActions(): array
	{
		return [
			'document download' => ['documents', 'babysitterDocuments-download'],
			'document delete' => ['documents', 'babysitterDocuments-delete'],
			'video download' => ['video', 'babysitterVideos-download'],
			'video playback' => ['video', 'babysitterVideos-play'],
			'video delete' => ['video', 'babysitterVideos-delete'],
		];
	}


	#[DataProvider('privateFileActions')]
	public function testOtherCardCannotAccessOrChangeFile(string $tab, string $signal): void
	{
		$firstBefore = $this->readFileState($this->first);
		$secondBefore = $this->readFileState($this->second);
		$auditCount = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*');
		foreach ([[$this->first, $this->second], [$this->second, $this->first]] as [$owner, $other]) {
			$link = $this->getLink($this->openTab($owner, $tab), $signal);
			self::assertSame($owner->getPagePath(), parse_url($link, PHP_URL_PATH));
			$query = parse_url($link, PHP_URL_QUERY);
			self::assertIsString($query);
			$response = $this->web->get($other->getPagePath() . '?' . $query);
			self::assertSame(404, $response->getStatusCode());
			self::assertFalse($response->hasHeader('Content-Disposition'));
			$reply = str_replace('\\', '/', Message::toString($response));
			foreach ([$owner, $other] as $card) {
				foreach ([
					$card->surname, $card->notice, basename($card->documentPath), $card->videoName, basename($card->videoPath),
					'babysitters/' . $card->ownerId, 'Document content ' . $card->label, 'Video content ' . $card->label,
				] as $privateValue) {
					self::assertStringNotContainsString($privateValue, $reply);
				}
			}
			self::assertSame($firstBefore, $this->readFileState($this->first));
			self::assertSame($secondBefore, $this->readFileState($this->second));
			self::assertSame($auditCount, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
		}
	}


	public function testDeletingOwnFilesPreservesTheOtherCard(): void
	{
		$card = $this->first;
		$otherBefore = $this->readFileState($this->second);
		$logs = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME);
		$auditCount = $logs->count('*');
		$link = $this->getLink($this->openTab($card, 'documents'), 'babysitterDocuments-delete');
		self::assertSame(302, $this->web->get($link)->getStatusCode());
		$document = $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($card->documentId);
		self::assertNotNull($document);
		self::assertSame(0, (int) $document[FileTableMap::COL_ACTIVE]);
		self::assertSame($card->documentContents, file_get_contents($card->documentPath));
		self::assertSame($auditCount + 1, $logs->count('*'));
		self::assertSame($otherBefore, $this->readFileState($this->second));

		$link = $this->getLink($this->openTab($card, 'video'), 'babysitterVideos-delete');
		self::assertSame(302, $this->web->get($link)->getStatusCode());
		$video = $this->getDatabase()->table(BabysitterVideoTableMap::TABLE_NAME)->get($card->videoId);
		self::assertNotNull($video);
		self::assertSame(0, (int) $video[BabysitterVideoTableMap::COL_ACTIVE]);
		self::assertSame($this->userId, (int) $video[BabysitterVideoTableMap::COL_DELETED_BY_USER_ID]);
		self::assertNotNull($video[BabysitterVideoTableMap::COL_DELETED_AT]);
		clearstatcache(true, $card->videoPath);
		self::assertFileDoesNotExist($card->videoPath);
		self::assertSame($auditCount + 2, $logs->count('*'));
		self::assertSame($otherBefore, $this->readFileState($this->second));
	}


	private function openTab(BabysitterPrivateFiles $card, string $tab): string
	{
		$response = $this->web->get($card->getPagePath() . '?tab=' . $tab);
		self::assertSame(200, $response->getStatusCode());
		return (string) $response->getBody();
	}


	private function getLink(string $html, string $signal): string
	{
		$dom = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
		$attribute = $signal === 'babysitterVideos-play' ? 'src' : 'href';
		$links = $dom->querySelectorAll('[' . $attribute . '*="do=' . $signal . '"]');
		self::assertCount(1, $links, $signal);
		$link = $links->item(0)?->getAttribute($attribute);
		self::assertIsString($link);
		return $link;
	}


	private function assertFileResponse(ResponseInterface $response, string $disposition, string $name, string $contents): void
	{
		self::assertSame(200, $response->getStatusCode());
		self::assertStringStartsWith($disposition . ';', $response->getHeaderLine('Content-Disposition'));
		self::assertStringContainsString($name, $response->getHeaderLine('Content-Disposition'));
		self::assertSame($contents, (string) $response->getBody());
	}


	/** @return array{document: array<string, mixed>, video: array<string, mixed>, documentHash: string, videoHash: string} */
	private function readFileState(BabysitterPrivateFiles $card): array
	{
		self::assertFileExists($card->documentPath);
		self::assertFileExists($card->videoPath);
		$documentHash = hash_file('sha256', $card->documentPath);
		$videoHash = hash_file('sha256', $card->videoPath);
		self::assertIsString($documentHash);
		self::assertIsString($videoHash);
		return [
			'document' => $this->readRawRow(FileTableMap::TABLE_NAME, $card->documentId),
			'video' => $this->readRawRow(BabysitterVideoTableMap::TABLE_NAME, $card->videoId),
			'documentHash' => $documentHash,
			'videoHash' => $videoHash,
		];
	}


	/** @return array<string, mixed> */
	private function readRawRow(string $table, int $id): array
	{
		$statement = $this->getDatabase()->getConnection()->getPdo()->prepare('SELECT * FROM ' . $table . ' WHERE id = ?');
		$statement->execute([$id]);
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		self::assertIsArray($row);
		return $row;
	}
}
