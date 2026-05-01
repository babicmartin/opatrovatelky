<?php declare(strict_types=1);

namespace App\Model\DataProvider\Directory;

final readonly class StorageDirProvider
{
	public function __construct(
		private string $userImages,
		private string $userImagesEmpty,
	) {
	}

	public function getUserImages(): string
	{
		return $this->userImages;
	}

	public function getUserImagesEmpty(): string
	{
		return $this->userImagesEmpty;
	}
}
