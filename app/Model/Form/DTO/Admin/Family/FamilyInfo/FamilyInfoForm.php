<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Family\FamilyInfo;

final readonly class FamilyInfoForm
{
	public function __construct(
		public int $id,
		public int $type,
		public int $partnerId,
		public int $acquiredByUserId,
		public int $userId,
		public int $status,
		public string $phone,
		public ?\DateTimeImmutable $dateStart,
		public ?\DateTimeImmutable $dateTo,
		public int $orderStatus,
		public int $contractStatus,
		public int $workStatusStaff,
		public string $projectDescription,
		public string $projectPositions,
		public string $projectAvailablePositions,
	) {
	}
}
