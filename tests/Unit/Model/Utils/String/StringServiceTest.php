<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\String;

use App\Model\Utils\String\StringService;
use InvalidArgumentException;
use Tests\Support\PHPUnit\TestCase;

final class StringServiceTest extends TestCase
{
	private StringService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->service = new StringService();
	}

	public function testRemoveAllWhiteSpacesKeepsNullAndRemovesWhitespace(): void
	{
		self::assertNull($this->service->removeAllWhiteSpaces(null));
		self::assertSame('abc', $this->service->removeAllWhiteSpaces(" a \n b\tc "));
	}

	public function testRemoveCharactersFromStartValidatesCount(): void
	{
		self::assertSame('st', $this->service->removeCharactersFromStart('test', 2));
		self::assertSame('', $this->service->removeCharactersFromStart('test', 10));

		$this->expectException(InvalidArgumentException::class);
		$this->service->removeCharactersFromStart('test', -1);
	}

	public function testExplodeRejectsEmptySeparator(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->service->explode('', 'a,b');
	}

	public function testTextTransformations(): void
	{
		self::assertSame('Jan Cap', $this->service->removeDiacritics('Ján Čáp'));
		self::assertSame('opatrovatelka', $this->service->webalize('Opatrovateľka'));
		self::assertSame('&lt;strong&gt;Test&lt;/strong&gt;', $this->service->htmlSpecialChars('<strong>Test</strong>'));
	}
}
