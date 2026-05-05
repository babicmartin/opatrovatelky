<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Babysitter\BabysitterMain;

final readonly class BabysitterMainForm
{
	public function __construct(
		public int $id,
		public int $type,
		public int $agencyId,
		public int $workingStatus,
		public int $status,
		public int $firstContactUserId,
		public int $blacklist,
		public string $notice,
	) {
	}
}
