<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Partner\PartnerUpdate;

final readonly class PartnerUpdateForm
{
	public function __construct(
		public int $id,
		public string $name,
		public string $street,
		public string $streetNumber,
		public string $psc,
		public string $city,
		public int $state,
		public ?\DateTimeImmutable $dateStart,
		public string $personSurname,
		public string $personName,
		public string $ico,
		public string $icDph,
		public string $web,
		public string $phone,
		public string $email,
		public int $status,
		public string $notice,
	) {
	}
}
