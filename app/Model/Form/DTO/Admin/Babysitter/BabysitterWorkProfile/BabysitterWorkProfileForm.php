<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Babysitter\BabysitterWorkProfile;

final readonly class BabysitterWorkProfileForm
{
	/**
	 * @param list<int> $qualificationIds
	 * @param list<int> $preferenceIds
	 */
	public function __construct(
		public int $id,
		public array $qualificationIds,
		public array $preferenceIds,
	) {
	}
}
