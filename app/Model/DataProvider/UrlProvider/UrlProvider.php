<?php declare(strict_types=1);

namespace App\Model\DataProvider\UrlProvider;

final readonly class UrlProvider
{
	public function __construct(
		private string $appName,
	) {
	}

	public function getAppName(): string
	{
		return $this->appName;
	}
}
