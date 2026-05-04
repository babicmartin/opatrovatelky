<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate;

final readonly class UserPasswordUpdateForm
{
	public function __construct(
		private string $password,
	) {
	}

	public function getPassword(): string
	{
		return $this->password;
	}
}
