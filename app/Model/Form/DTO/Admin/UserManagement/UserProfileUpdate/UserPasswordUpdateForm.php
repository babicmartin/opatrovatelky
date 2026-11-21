<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate;

final readonly class UserPasswordUpdateForm
{
	public function __construct(
		#[\SensitiveParameter] private string $password,
		#[\SensitiveParameter] public string $currentPassword,
	) {
	}

	public function getPassword(): string
	{
		return $this->password;
	}
}
