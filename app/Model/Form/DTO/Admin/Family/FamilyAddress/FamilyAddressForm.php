<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Family\FamilyAddress;

final readonly class FamilyAddressForm
{
	public function __construct(
		public int $id,
		public string $companyName,
		public string $name,
		public string $surname,
		public string $street,
		public string $streetNumber,
		public string $psc,
		public string $city,
		public string $billing,
		public string $employer,
		public string $accommodationAddress,
		public string $notice,
		public string $personSurname,
		public string $personName,
		public string $personPhone,
		public string $personEmail,
		public string $patientPhone,
	) {
	}
}
