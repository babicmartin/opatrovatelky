<?php declare(strict_types=1);

namespace Tests\Support\PHPUnit;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
	protected function tearDown(): void
	{
		Mockery::close();

		parent::tearDown();
	}
}
