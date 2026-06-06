<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Validator;

use App\Model\Utils\Validator\UrlValidator;
use Tests\Support\PHPUnit\TestCase;

final class UrlValidatorTest extends TestCase
{
	public function testValidAndInvalidUrls(): void
	{
		$validator = new UrlValidator();

		self::assertTrue($validator->isUrl('https://example.test'));
		self::assertTrue($validator->isUrl('http://example.test/path?q=1'));
		self::assertFalse($validator->isUrl('example.test'));
		self::assertFalse($validator->isUrl('not a url'));
	}
}
