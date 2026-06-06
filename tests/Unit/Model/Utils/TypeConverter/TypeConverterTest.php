<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\TypeConverter;

use App\Model\Utils\String\StringService;
use App\Model\Utils\TypeConverter\TypeConverter;
use Tests\Support\PHPUnit\TestCase;

final class TypeConverterTest extends TestCase
{
	private TypeConverter $converter;

	protected function setUp(): void
	{
		parent::setUp();
		$this->converter = new TypeConverter(new StringService());
	}

	public function testIntToBoolTreatsOnlyOneAsTrue(): void
	{
		self::assertTrue($this->converter->intToBool(1));
		self::assertFalse($this->converter->intToBool(0));
		self::assertFalse($this->converter->intToBool(2));
	}

	public function testBoolToInt(): void
	{
		self::assertSame(1, $this->converter->boolToInt(true));
		self::assertSame(0, $this->converter->boolToInt(false));
	}

	public function testStringToFloatNormalizesCommaDecimal(): void
	{
		self::assertSame(1.5, $this->converter->stringToFloat('1,5'));
		self::assertSame(1234.5, $this->converter->stringToFloat('1234.5'));
	}

	public function testStringToIntAndIntToString(): void
	{
		self::assertSame(42, $this->converter->stringToInt('42abc'));
		self::assertSame('7', $this->converter->intToString(7));
	}
}
