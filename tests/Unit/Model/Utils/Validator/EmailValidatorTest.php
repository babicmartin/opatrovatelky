<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Validator;

use App\Model\Utils\Validator\EmailValidator;
use Tests\Support\PHPUnit\TestCase;

final class EmailValidatorTest extends TestCase
{
	public function testValidAndInvalidEmails(): void
	{
		$validator = new EmailValidator();

		self::assertTrue($validator->isEmail('user@example.test'));
		self::assertFalse($validator->isEmail('user@'));
		self::assertFalse($validator->isEmail('not-an-email'));
	}
}
