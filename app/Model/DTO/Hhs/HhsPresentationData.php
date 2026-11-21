<?php declare(strict_types=1);

namespace App\Model\DTO\Hhs;

final readonly class HhsPresentationData
{
	/** @param list<array{period: string, country: string, city: string}> $positions */
	public function __construct(
		public string $fullName,
		public string $birthday,
		public string $city,
		public string $phone,
		public string $availableFrom,
		public string $assessment,
		public string $imageFilename,
		public string $smoker,
		public string $drivingLicence,
		public string $nightWork,
		public string $height,
		public string $weight,
		public array $positions,
		public string $notes,
		public string $germanGrade,
		public string $otherLanguages,
		public string $monthlyCosts,
		public string $travelCosts,
		public string $holidayRate,
		public string $author,
		public string $contactPhone,
	) {
	}
}
