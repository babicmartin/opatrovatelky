<?php declare(strict_types = 1);

namespace App\Model\DataProvider\Directory;

final readonly class DirectoryProvider
{
	public function __construct(
		private string $appDir,
		private string $tempDir,
		private string $vendorDir,
		private string $rootDir,
		private string $logDir,
	)
	{
	}

	public function getAppDir(): string
	{
		return $this->appDir;
	}

	public function getTempDir(): string
	{
		return $this->tempDir;
	}

	public function getVendorDir(): string
	{
		return $this->vendorDir;
	}

	public function getRootDir(): string
	{
		return $this->rootDir;
	}

	public function getLogDir(): string
	{
		return $this->logDir;
	}





}