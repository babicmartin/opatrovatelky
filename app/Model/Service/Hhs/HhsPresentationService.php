<?php declare(strict_types=1);

namespace App\Model\Service\Hhs;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Repository\HhsPresentationRepository;
use DateTimeImmutable;
use Nette\Utils\FileSystem;
use Psr\Clock\ClockInterface;
use RuntimeException;
use ZipArchive;

final readonly class HhsPresentationService
{
	public function __construct(
		private HhsPresentationRepository $repository,
		private HhsPresentationWriter $writer,
		private DirectoryProvider $directories,
		private StorageDirProvider $storage,
		private ClockInterface $clock,
		private string $templatePath,
		private string $exportDirectory,
	) {
	}


	public function generate(int $babysitterId): string
	{
		$generatedAt = $this->clock->now();
		$data = $this->repository->find($babysitterId, $generatedAt);
		if ($data === null) {
			throw new RuntimeException('Prezentácia je dostupná iba pre existujúci profil opatrovateľky.');
		}

		FileSystem::createDir($this->exportDirectory);
		$temporary = tempnam($this->exportDirectory, '.hhs-');
		if ($temporary === false) {
			throw new RuntimeException('Cannot create a temporary presentation.');
		}

		try {
			FileSystem::copy($this->templatePath, $temporary, overwrite: true);
			$zip = new ZipArchive;
			if ($zip->open($temporary) !== true) {
				throw new RuntimeException('Cannot open the HHS template.');
			}
			try {
				$this->writer->fill($zip, $data, $generatedAt, $this->findPhoto($data->imageFilename));
			} finally {
				if (!$zip->close()) {
					throw new RuntimeException('Cannot save the HHS presentation.');
				}
			}

			$path = $this->getAbsolutePath($babysitterId);
			if (!rename($temporary, $path)) {
				throw new RuntimeException('Cannot publish the HHS presentation.');
			}
			return $path;
		} finally {
			if (is_file($temporary)) {
				unlink($temporary);
			}
		}
	}


	public function getAbsolutePath(int $babysitterId): string
	{
		return $this->exportDirectory . '/' . $babysitterId . '.pptx';
	}


	public function getGeneratedAt(int $babysitterId): ?DateTimeImmutable
	{
		$path = $this->getAbsolutePath($babysitterId);
		$timestamp = is_file($path) ? filemtime($path) : false;
		return $timestamp === false ? null : $this->clock->now()->setTimestamp($timestamp);
	}


	private function findPhoto(string $filename): ?string
	{
		if ($filename === '' || str_contains($filename, '\\') || basename($filename) !== $filename) {
			return null;
		}
		foreach ([$this->storage->getUserImages(), 'img/opatrovatelka'] as $directory) {
			$path = $this->directories->getRootDir() . '/www/' . $directory . '/' . $filename;
			if (is_file($path)) {
				return $path;
			}
		}
		return null;
	}
}
