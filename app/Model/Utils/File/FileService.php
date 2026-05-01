<?php declare(strict_types = 1);

namespace App\Model\Utils\File;

use App\Model\Utils\String\StringService;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;

class FileService
{
	private StringService $stringService;

	public function __construct(
		StringService $stringService
	)
	{

		$this->stringService = $stringService;
	}


	public function getFileSize(FileUpload $fileUpload): int
	{
		return $fileUpload->getSize();
	}

	public function isReadable(string $filename): bool
	{
		return is_readable($filename);
	}

	public function fileExists(string $path): bool
	{
		return file_exists($path);
	}

	public function getFileExtension(string $path): string
	{
		return (string) $this->stringService->lower(pathinfo($path, PATHINFO_EXTENSION));
	}


	public function removeFile(string $path): void
	{
		FileSystem::delete($path);

		//unlink($path);
	}

	public function copy(string $origin, string $target, bool $overwrite = true): void
	{
		FileSystem::copy($origin, $target, $overwrite);
	}

	public function rename(string $origin, string $target, bool $overwrite = true): void
	{
		FileSystem::rename($origin, $target, $overwrite);
	}



}