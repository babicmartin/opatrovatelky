<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Family\FamilyShortInfo;

final readonly class FamilyShortInfoForm
{
	public function __construct(
		public int $id,
		public string $clientNumber,
		public string $deProjectNumber,
		public int $state,
	) {
	}
}
