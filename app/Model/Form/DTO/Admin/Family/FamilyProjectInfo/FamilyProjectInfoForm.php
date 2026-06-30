<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Family\FamilyProjectInfo;

final readonly class FamilyProjectInfoForm
{
	public function __construct(
		public int $id,
		public string $projectDescription,
		public string $projectPositions,
		public string $projectAvailablePositions,
	) {
	}
}
