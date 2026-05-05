<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Babysitter\BabysitterAddress;

final readonly class BabysitterAddressForm
{
	public function __construct(
		public int $id,
		public string $name,
		public string $surname,
		public ?\DateTimeImmutable $birthday,
		public int $pohlavie,
		public int $country,
		public string $city,
		public string $street,
		public string $postalCode,
		public string $phone,
		public string $phone2,
		public string $email,
		public string $height,
		public string $weight,
		public string $about,
		public string $requirements,
		public string $contactPersonName,
		public string $contactPersonPhone,
	) {
	}
}
