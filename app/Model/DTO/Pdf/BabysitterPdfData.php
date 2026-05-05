<?php declare(strict_types=1);

namespace App\Model\DTO\Pdf;

final readonly class BabysitterPdfData
{
	/**
	 * @param array<string, mixed> $babysitter
	 * @param list<array{id:int,german:string}> $diseases
	 * @param list<int> $selectedDiseaseIds
	 * @param array<int, array{slovak:string,german:string}> $translations indexed by translation row id
	 */
	public function __construct(
		public array $babysitter,
		public string $countryName,
		public string $smokerGerman,
		public string $allergyGerman,
		public string $driverLicenceGerman,
		public string $readyDriveGerman,
		public string $educationGerman,
		public string $languageGerman,
		public int $languageStars,
		public array $diseases,
		public array $selectedDiseaseIds,
		public array $translations,
	) {
	}
}
