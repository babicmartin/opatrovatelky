<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Checkbox;

use App\Model\Utils\Checkbox\CheckboxService;
use Tests\Support\PHPUnit\TestCase;

final class CheckboxServiceTest extends TestCase
{
	private CheckboxService $service;

	protected function setUp(): void
	{
		parent::setUp();
		$this->service = new CheckboxService();
	}

	public function testConvertToIntOnlyMapsOnToOne(): void
	{
		self::assertSame(1, $this->service->convertToInt('on'));
		self::assertSame(0, $this->service->convertToInt('off'));
		self::assertSame(0, $this->service->convertToInt(null));
		self::assertSame(0, $this->service->convertToInt(''));
	}

	public function testConvertToBoolOnlyMapsOnToTrue(): void
	{
		self::assertTrue($this->service->convertToBool('on'));
		self::assertFalse($this->service->convertToBool('1'));
		self::assertFalse($this->service->convertToBool(null));
	}
}
