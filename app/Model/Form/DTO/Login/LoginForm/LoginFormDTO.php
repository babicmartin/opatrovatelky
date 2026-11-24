<?php declare(strict_types = 1);

namespace App\Model\Form\DTO\Login\LoginForm;

final readonly class LoginFormDTO
{
	public function __construct(
		private string $email,
		#[\SensitiveParameter]
		private string $password,
	)
	{
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getPassword(): string
	{
		return $this->password;
	}


}