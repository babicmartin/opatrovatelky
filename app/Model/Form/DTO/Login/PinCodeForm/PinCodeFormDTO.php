<?php declare(strict_types = 1);

namespace App\Model\Form\DTO\Login\PinCodeForm;

final readonly class PinCodeFormDTO
{
	public function __construct(
		private string $pinCode,
		private string $email,
	)
	{
	}

	public function getPinCode(): string
	{
		return $this->pinCode;
	}

	public function getEmail(): string
	{
		return $this->email;
	}


}