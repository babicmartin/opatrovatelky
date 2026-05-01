<?php declare(strict_types = 1);

namespace App\Model\Form\DTO\Admin\PasswordUpdate;

class PasswordUpdateForm
{

	public function __construct(
		private string $password,

	)
	{
	}

	public function getPassword(): string
	{
		return $this->password;
	}

	public function setPassword(string $password): void
	{
		$this->password = $password;
	}

}