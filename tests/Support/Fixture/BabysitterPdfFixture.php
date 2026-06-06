<?php declare(strict_types=1);

namespace Tests\Support\Fixture;

use App\Model\DTO\Pdf\BabysitterPdfData;
use DateTimeImmutable;
use Latte\Engine;

/**
 * Deterministic fixture + renderer for the babysitter PDF Latte template, shared by the
 * snapshot and performance suites so the template can be exercised without a database or mpdf.
 */
final class BabysitterPdfFixture
{
	private const string TEMPLATE = '/app/Model/Service/Pdf/templates/babysitter.pdf.latte';

	public static function data(): BabysitterPdfData
	{
		$babysitter = [
			'name' => 'Anna',
			'surname' => 'Musterfrau',
			'about' => 'Erfahrene Betreuerin.',
			'birthday' => new DateTimeImmutable('1985-04-12'),
			'profilShowContact' => 1,
			'city' => 'Bratislava',
			'street' => 'Hlavna 1',
			'country' => 2,
			'phone' => '+421900000000',
			'contactPersonPhone' => '+421911111111',
			'email' => 'anna@example.test',
			'height' => 168,
			'weight' => 64,
			'allergyDetail' => 'Pollen',
			'languageSkillsOther' => 'Englisch A2',
			'courseDetail' => 'Pflegekurs 2020',
			'howLongWork' => '5 Jahre',
			'howLongWorkGerman' => '2 Jahre in DE',
			'dailyCare' => 1,
			'hourlyCare' => 0,
			'timeScale' => '6 Wochen',
			'workPlace' => 'Haushalt',
			'workDescription' => 'Grundpflege und Haushalt.',
			'generalActivities' => 'Kochen, Einkaufen.',
			'ratingAgency' => 'Sehr zuverlaessig.',
		];

		return new BabysitterPdfData(
			babysitter: $babysitter,
			countryName: 'Slowakei',
			smokerGerman: 'Nichtraucher',
			allergyGerman: 'Keine',
			driverLicenceGerman: 'Klasse B',
			readyDriveGerman: 'Ja',
			educationGerman: 'Mittelschule',
			languageGerman: 'Gut',
			languageStars: 4,
			diseases: [
				['id' => 1, 'german' => 'Demenz'],
				['id' => 2, 'german' => 'Diabetes'],
			],
			selectedDiseaseIds: [1],
			translations: self::translations(),
		);
	}

	public static function render(BabysitterPdfData $data, string $tempDir): string
	{
		if (!is_dir($tempDir)) {
			mkdir($tempDir, 0777, true);
		}

		$engine = new Engine();
		$engine->setTempDirectory($tempDir);
		$engine->setAutoRefresh(true);

		return $engine->renderToString(dirname(__DIR__, 3) . self::TEMPLATE, [
			'data' => $data,
			'babysitter' => $data->babysitter,
			'translation' => $data->translations,
			'wwwDir' => 'WWW',
			'imagesDir' => 'IMAGES',
			'profileImageHtml' => '<img src="IMAGES/profile.png">',
		]);
	}

	/**
	 * @return array<int, array{slovak:string,german:string}>
	 */
	private static function translations(): array
	{
		$indexes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 22, 23, 24, 25, 26, 27, 28];
		$translations = [];
		foreach ($indexes as $index) {
			$translations[$index] = ['slovak' => 'SK-' . $index, 'german' => 'DE-' . $index];
		}

		return $translations;
	}
}
