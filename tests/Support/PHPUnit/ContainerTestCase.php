<?php declare(strict_types=1);

namespace Tests\Support\PHPUnit;

use App\Bootstrap;
use Nette\DI\Container;

abstract class ContainerTestCase extends TestCase
{
	private static ?Container $container = null;

	protected function getContainer(): Container
	{
		if (self::$container === null) {
			self::$container = Bootstrap::boot()->createContainer();
			restore_error_handler();
			restore_exception_handler();
		}

		return self::$container;
	}
}
