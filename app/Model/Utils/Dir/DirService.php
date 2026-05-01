<?php declare(strict_types = 1);

namespace App\Model\Utils\Dir;

use DirectoryIterator;
use Exception;
use Nette\Utils\FileSystem;

class DirService
{
	public function renameDir(string $dir, string $newDir): bool
	{
		if ($this->isDir($dir) === false) {
			return false;
		}

		if ($this->isDir($newDir) === true) {
			//throw new Exception("The new directory already exists.");
			return false;
		}

		FileSystem::rename($dir, $newDir);

		return true;
	}


	public function isDir(string $dir): bool
	{
		if (is_dir($dir)) {
			return true;
		}

		return false;
	}

	public function createDir(string $dir): void
	{
		if ($this->isDir($dir) === false) {
			FileSystem::createDir($dir);
		}
	}

	public function removeDir(string $path): void
	{
		FileSystem::delete($path);
	}

	/**
	 * @return array<int, string>
	 */
	public function scanDir(string $dir): array
	{
		$dirFiles = [];


		if ($this->isDir($dir) === false) {
			return $dirFiles;
		}

		$files = new DirectoryIterator($dir);


		foreach ($files as $file) {
			if ($file->isDot() === false && $file->getFilename() !== '.htaccess') {
				$dirFiles[] = $file->getFilename();
			}
		}

		return $dirFiles;
	}

}