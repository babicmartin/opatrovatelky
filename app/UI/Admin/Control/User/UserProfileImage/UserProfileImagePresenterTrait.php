<?php declare(strict_types=1);

namespace App\UI\Admin\Control\User\UserProfileImage;

trait UserProfileImagePresenterTrait
{
	private UserProfileImageControlFactory $userProfileImageControlFactory;

	public function injectUserProfileImageControl(
		UserProfileImageControlFactory $userProfileImageControlFactory,
	): void {
		$this->userProfileImageControlFactory = $userProfileImageControlFactory;
	}

	protected function createComponentUserProfileImage(): UserProfileImageControl
	{
		$userId = $this->getUser()->getId();

		return $this->userProfileImageControlFactory->create(is_int($userId) ? $userId : 0);
	}
}
