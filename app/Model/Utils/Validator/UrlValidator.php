<?php declare(strict_types = 1);

namespace App\Model\Utils\Validator;

use Nette\Utils\Validators;

class UrlValidator
{
	public function isUrl(string $url): bool
	{
		return Validators::isUrl($url);
	}
}