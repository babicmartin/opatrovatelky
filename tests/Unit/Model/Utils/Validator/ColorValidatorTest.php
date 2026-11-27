<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Validator;

use App\Model\Utils\Validator\ColorValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PHPUnit\TestCase;

final class ColorValidatorTest extends TestCase
{
	/** @return iterable<string, array{string, bool}> */
	public static function values(): iterable
	{
		yield 'short hex' => ['#abc', true];
		yield 'hex' => ['#8A2062', true];
		yield 'hex with alpha' => ['#8A206280', true];
		yield 'name' => ['blue', true];
		yield 'name in upper case' => ['RED', true];
		yield 'empty' => ['', false];
		yield 'hex of a wrong length' => ['#8A206', false];
		yield 'hex without the hash' => ['8A2062', false];
		yield 'second declaration' => ['red;zoom:9', false];
		yield 'function' => ['url(//x)', false];
		yield 'quote' => ['red"', false];
		yield 'trailing new line' => ["red\n", false];
		yield 'space' => ['dark red', false];
	}

	#[DataProvider('values')]
	public function testOnlyAHexColourOrAColourNameIsAccepted(string $value, bool $expected): void
	{
		self::assertSame($expected, (new ColorValidator)->isColor($value));
	}
}
