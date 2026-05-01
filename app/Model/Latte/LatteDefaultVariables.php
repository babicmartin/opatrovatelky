<?php declare(strict_types = 1);

namespace App\Model\Latte;

final readonly class LatteDefaultVariables
{
	public function __construct(
		private int $cssVersion,
		private int $jsVersion,
	)
	{
	}

	public function getCssVersion(): int
	{
		return $this->cssVersion;
	}

	public function getJsVersion(): int
	{
		return $this->jsVersion;
	}


}