<?php declare(strict_types=1);

namespace App\UI\Admin\Control\User\UserProfileImage;

interface UserProfileImageControlFactory
{
	public function create(int $userId): UserProfileImageControl;
}
