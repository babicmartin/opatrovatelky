<?php declare(strict_types = 1);

namespace App\Model\Utils\Path;

use Nette\Utils\FileSystem;

class PathService
{
	public function joinPaths(string ...$segments): string
	{
		return FileSystem::joinPaths(...$segments);
	}

	public function normalizePath(string $path): string
	{
		return FileSystem::normalizePath($path);
	}
	
}