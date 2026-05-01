<?php declare(strict_types=1);

namespace App\UI\Admin\Control\User\UserProfileImage;

use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Repository\UserRepository;
use App\Model\Table\UserTableMap;
use Nette\Application\UI\Control;

class UserProfileImageControl extends Control
{
	public function __construct(
		private readonly int $userId,
		private readonly UserRepository $userRepository,
		private readonly StorageDirProvider $storageDirProvider,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/UserProfileImageControl.latte');

		$imagePath = $this->storageDirProvider->getUserImagesEmpty();

		$row = $this->userRepository->findById($this->userId);
		if ($row !== null) {
			$image = $row->{UserTableMap::COL_IMAGE};
			if (is_string($image) && $image !== '') {
				$imagePath = $this->storageDirProvider->getUserImages() . '/' . $image;
			}
		}

		$template->imagePath = $imagePath;
		$template->userId = $this->userId;
		$template->render();
	}
}
