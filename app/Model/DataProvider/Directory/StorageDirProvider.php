<?php declare(strict_types=1);

namespace App\Model\DataProvider\Directory;

final readonly class StorageDirProvider
{
	public function __construct(
		private string $userImages,
		private string $userImagesEmpty,
		private string $countryImages,
		private string $documents,
		private string $documentTypeImages,
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

	public function getCountryImages(): string
	{
		return $this->countryImages;
	}

	public function getDocuments(): string
	{
		return $this->documents;
	}

	public function getDocumentTypeImages(): string
	{
		return $this->documentTypeImages;
	}
}
