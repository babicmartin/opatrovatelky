<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Service\Pdf\BabysitterPdfService;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\TranslateTableMap;
use Nette\Utils\FileSystem;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

/**
 * Binary smoke for the babysitter PDF pipeline: drives the real BabysitterPdfService
 * (Latte template + pdf.css + mpdf) end to end and asserts mpdf writes a non-empty,
 * valid PDF file. The markup layer is pinned separately by PdfTemplateSnapshotTest;
 * this test guards that the actual mpdf binary output keeps working.
 *
 * A high, fixed id is used so the output never collides with real dev exports
 * (private/export/babysitter/pdf/{id}.pdf), and the file is removed afterwards.
 */
final class BabysitterPdfBinarySmokeTest extends DatabaseTestCase
{
	private const BabysitterId = 900001;

	public function testGenerateWritesNonEmptyPdf(): void
	{
		$this->seedPdfTranslations();
		TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_ID => self::BabysitterId,
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'PDF-SMOKE',
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Musterfrau',
		]);

		$service = $this->getContainer()->getByType(BabysitterPdfService::class);
		$path = $service->getAbsolutePath(self::BabysitterId);
		FileSystem::delete($path);

		try {
			$service->generate(self::BabysitterId);

			self::assertTrue($service->exists(self::BabysitterId), 'mpdf should write the PDF file.');

			$bytes = FileSystem::read($path);
			self::assertStringStartsWith('%PDF-', $bytes, 'Output must be a valid PDF binary.');
			self::assertStringContainsString('%%EOF', $bytes, 'PDF must contain the EOF marker.');
			self::assertGreaterThan(1000, strlen($bytes), 'A real rendered PDF is well above 1 KB.');
		} finally {
			FileSystem::delete($path);
		}
	}

	private function seedPdfTranslations(): void
	{
		foreach (range(1, 28) as $id) {
			TestDatabase::insert(TranslateTableMap::TABLE_NAME, [
				TranslateTableMap::COL_ID => $id,
				TranslateTableMap::COL_SLOVAK => 'SK-' . $id,
				TranslateTableMap::COL_GERMAN => 'DE-' . $id,
			]);
		}
	}
}
