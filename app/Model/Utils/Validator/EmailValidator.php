<?php declare(strict_types = 1);

namespace App\Model\Utils\Validator;

use Nette\Utils\Validators;

class EmailValidator
{
	public function isEmail(string $url): bool
	{
		return Validators::isEmail($url);
	}
}