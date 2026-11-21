<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Command\HhsPresentationCommand;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Service\Hhs\HhsPresentationService;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\UserTableMap;
use DateTimeImmutable;
use Dom\HTMLDocument;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;
use ZipArchive;

final class HhsPresentationHttpTest extends DatabaseTestCase
{
	private const FormId = 'frm-generateHhsPresentationForm';
	private TestWebClient $web;
	private int $userId;
	private int $id;
	private string $path;
	private string $page;


	protected function setUp(): void
	{
		parent::setUp();
		$this->id = TestDatabase::createBabysitter(['id' => random_int(1000000, 2000000)]);
		$this->path = $this->getContainer()->getByType(HhsPresentationService::class)->getAbsolutePath($this->id);
		$this->page = '/babysitter/update/' . $this->id . '?tab=pdf';
		$this->userId = TestDatabase::createUser();
		$this->web = new TestWebClient();
		self::assertSame(303, $this->web->login('test.user@example.test', 'secret123!')->getStatusCode());
	}


	protected function tearDown(): void
	{
		if (isset($this->web)) {
			$this->web->stop();
		}
		if (isset($this->path) && is_file($this->path)) {
			unlink($this->path);
		}
		parent::tearDown();
	}


	public function testCreateUpdateAndDownloadFromPdfTab(): void
	{
		$html = (string) $this->web->get($this->page)->getBody();
		self::assertStringContainsString('Vytvoriť prezentáciu pre HHS', $html);
		self::assertStringNotContainsString('Stiahnuť prezentáciu (.pptx)', $html);
		self::assertSame(404, $this->web->get($this->page . '&do=downloadHhsPresentation')->getStatusCode());

		$this->web->get($this->page . '&do=generateHhsPresentationForm-submit');
		$this->web->post($this->page, ['_do' => 'generateHhsPresentationForm-submit', '_token_' => 'invalid']);
		self::assertFileDoesNotExist($this->path);

		$html = $this->generate();
		self::assertStringContainsString('Aktualizovať prezentáciu pre HHS', $html);
		self::assertStringContainsString('Prezentácia pre HHS bola vygenerovaná.', $html);
		$this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->wherePrimary($this->id)
			->update(['hhs_additional_notes' => 'Updated HHS note']);
		$this->generate();

		$dom = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
		$download = $dom->querySelector('a[href*="do=downloadHhsPresentation"]');
		self::assertNotNull($download);
		$icon = $download->querySelector('img');
		self::assertNotNull($icon);
		self::assertSame('Stiahnuť prezentáciu (.pptx)', $icon->getAttribute('alt'));
		$iconPath = $icon->getAttribute('src');
		self::assertIsString($iconPath);
		$iconResponse = $this->web->get($iconPath);
		self::assertSame(200, $iconResponse->getStatusCode());
		self::assertSame('image/png', $iconResponse->getHeaderLine('Content-Type'));

		$link = $download->getAttribute('href');
		self::assertIsString($link);
		$response = $this->web->get($link);
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('application/vnd.openxmlformats-officedocument.presentationml.presentation', $response->getHeaderLine('Content-Type'));
		self::assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
		self::assertStringContainsString('hhs-' . $this->id . '.pptx', $response->getHeaderLine('Content-Disposition'));
		self::assertSame(file_get_contents($this->path), (string) $response->getBody());

		$zip = new ZipArchive;
		self::assertTrue($zip->open($this->path, ZipArchive::CHECKCONS) === true);
		try {
			self::assertStringContainsString('Updated HHS note', (string) $zip->getFromName('ppt/slides/slide2.xml'));
		} finally {
			$zip->close();
		}
	}


	public function testFailedUpdateKeepsConsoleExportAndItsTimestamp(): void
	{
		$command = new CommandTester($this->getContainer()->getByType(HhsPresentationCommand::class));
		self::assertSame(0, $command->execute(['id' => $this->id]));
		$previousTime = new DateTimeImmutable('2025-07-01 10:34:00 UTC');
		self::assertTrue(touch($this->path, $previousTime->getTimestamp()));
		$original = file_get_contents($this->path);
		$html = (string) $this->web->get($this->page)->getBody();
		self::assertStringContainsString('Aktualizovať prezentáciu pre HHS', $html);
		self::assertStringContainsString('01.07.2025 12:34', $html);

		$images = $this->getContainer()->getByType(StorageDirProvider::class)->getUserImages();
		$invalidPhoto = tempnam(dirname(__DIR__, 2) . '/www/' . $images, 'hhs-test-');
		self::assertIsString($invalidPhoto);
		try {
			$this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->wherePrimary($this->id)
				->update(['image' => basename($invalidPhoto)]);
			$html = $this->generate();
			self::assertStringContainsString('Prezentáciu pre HHS sa nepodarilo vygenerovať.', $html);
			self::assertStringContainsString('01.07.2025 12:34', $html);
			self::assertStringContainsString('Stiahnuť prezentáciu (.pptx)', $html);
			self::assertSame($original, (string) $this->web->get($this->page . '&do=downloadHhsPresentation')->getBody());
		} finally {
			unlink($invalidPhoto);
		}
	}


	public function testBothOperationsRequirePermissionAndAnActiveBabysitter(): void
	{
		$this->generate();
		$profile = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->wherePrimary($this->id);
		$user = $this->getDatabase()->table(UserTableMap::TABLE_NAME)->wherePrimary($this->userId);
		$user->update(['permission' => 2]);
		$this->assertAccessDenied(403);
		$user->update(['permission' => 10]);
		$profile->update(['type' => 2]);
		$html = (string) $this->web->get($this->page)->getBody();
		self::assertStringNotContainsString('Prezentácia pre HHS', $html);
		$this->assertAccessDenied(403);
		$profile->update(['type' => 1, 'active' => 0]);
		$this->assertAccessDenied(404);
		$profile->delete();
		$this->assertAccessDenied(404);
	}


	private function generate(): string
	{
		$response = $this->web->submitForm($this->page, self::FormId);
		self::assertSame(303, $response->getStatusCode());
		$location = $response->getHeaderLine('Location');
		self::assertSame('/babysitter/update/' . $this->id, parse_url($location, PHP_URL_PATH));
		self::assertStringContainsString('tab=pdf', $location);
		$page = $this->web->get($location);
		self::assertSame(200, $page->getStatusCode());
		return (string) $page->getBody();
	}


	private function assertAccessDenied(int $status): void
	{
		self::assertSame($status, $this->web->post($this->page, ['_do' => 'generateHhsPresentationForm-submit'])->getStatusCode());
		self::assertSame($status, $this->web->get($this->page . '&do=downloadHhsPresentation')->getStatusCode());
	}
}
