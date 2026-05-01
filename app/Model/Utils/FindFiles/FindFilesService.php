<?php declare(strict_types = 1);

namespace App\Model\Utils\FindFiles;

use Nette\Utils\FileInfo;
use Nette\Utils\Finder;

class FindFilesService
{
	/**
	 * @return array<int, FileInfo>
	 */
	public function searchFilesRecursively(string $pattern, string $dir): array
	{
		$files = [];

		foreach (Finder::findFiles($pattern)->from($dir) as $file) {
			$files[] = $file;
		}

		return $files;
	}

	/**
	 * @return array<int, FileInfo>
	 */
	public function searchFilesNonRecursively(string $pattern, string $dir): array
	{
		$files = [];

		foreach (Finder::findFiles($pattern)->in($dir) as $file) {
			$files[] = $file;
		}

		return $files;
	}

	/**
	 * @return array<int, FileInfo>
	 */
	public function searchFilesNonRecursivelyWithConditions(string $pattern, string $dir, ?string $date = null, ?string $dateOperator = null, bool $sortBySize = false, bool $sortByName = false, ?string $sortDirection = null): array
	{
		$files = [];

		$finder = Finder::findFiles($pattern)->in($dir);

		if ($date !== null && $dateOperator !== null) {
			/** @var '!='|'!=='|'<'|'<='|'<>'|'='|'=='|'==='|'>'|'>=' $dateOperator */
			$finder->date($dateOperator, $date);
		}

		if ($sortBySize === true) {
			if ($sortDirection === 'DESC') {
				$finder->sortBy(fn($a, $b) => $b->getSize() <=> $a->getSize());
			} else {
				$finder->sortBy(fn($a, $b) => $a->getSize() <=> $b->getSize());
			}
		}

		if ($sortByName === true) {
			if ($sortDirection === 'DESC') {
				$finder->sortBy(fn($a, $b) => $b->getFilename() <=> $a->getFilename());
			} else {
				$finder->sortBy(fn($a, $b) => $a->getFilename() <=> $b->getFilename());
			}
		}

		foreach ($finder as $file) {
			$files[] = $file;
		}

		return $files;
	}

}
