<?php declare(strict_types = 1);

namespace App\Model\Form\DTO\Admin\PinUpdate;

class PinUpdateForm
{
	public function __construct(
		private string $pinCode,
	)
	{
	}

	public function getPinCode(): string
	{
		return $this->pinCode;
	}

	public function setPinCode(string $pinCode): void
	{
		$this->pinCode = $pinCode;
	}


}