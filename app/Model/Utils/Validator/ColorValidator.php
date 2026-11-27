<?php declare(strict_types=1);

namespace App\Model\Utils\Validator;

class ColorValidator
{
	/**
	 * A hex colour or a CSS colour name. Nothing that could end a CSS declaration or an HTML attribute passes.
	 */
	public const string PATTERN = '~^(?:#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})|[a-z]{3,20})$~Di';

	public function isColor(string $value): bool
	{
		return preg_match(self::PATTERN, $value) === 1;
	}
}
