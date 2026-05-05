<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Babysitter\BabysitterProfile;

final readonly class BabysitterProfileForm
{
	public function __construct(
		public int $id,
		public int $smoker,
		public int $allergy,
		public string $allergyDetail,
		public string $howLongWork,
		public string $howLongWorkGerman,
		public int $dailyCare,
		public int $hourlyCare,
		public int $accommodationType,
		public string $timeScale,
		public string $workPlace,
		public string $jobPositionInterest,
		public string $workDescription,
		public string $generalActivities,
		public string $ratingAgency,
		public int $workShoes,
		public string $shoeSize,
		public string $germanTaxId,
		/** @var list<int> */
		public array $diseaseIds,
	) {
	}
}
