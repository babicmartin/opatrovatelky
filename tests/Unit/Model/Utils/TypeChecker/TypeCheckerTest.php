<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\TypeChecker;

use App\Model\Utils\TypeChecker\TypeChecker;
use Tests\Support\PHPUnit\TestCase;

final class TypeCheckerTest extends TestCase
{
	private TypeChecker $checker;

	protected function setUp(): void
	{
		parent::setUp();
		$this->checker = new TypeChecker();
	}

	public function testIsString(): void
	{
		self::assertTrue($this->checker->isString('x'));
		self::assertFalse($this->checker->isString(1));
	}

	public function testIsInt(): void
	{
		self::assertTrue($this->checker->isInt(1));
		self::assertFalse($this->checker->isInt('1'));
	}

	public function testIsBool(): void
	{
		self::assertTrue($this->checker->isBool(false));
		self::assertFalse($this->checker->isBool(0));
	}
}
