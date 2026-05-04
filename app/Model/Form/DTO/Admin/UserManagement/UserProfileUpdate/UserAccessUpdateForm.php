<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate;

final readonly class UserAccessUpdateForm
{
	public function __construct(
		private int $permission,
		private int $active,
	) {
	}

	public function getPermission(): int
	{
		return $this->permission;
	}

	public function getActive(): int
	{
		return $this->active;
	}
}
