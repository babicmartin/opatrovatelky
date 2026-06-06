<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Model\Utils\String\StringService;
use Nette\Application\Routers\RouteList;
use Tests\Support\PHPUnit\ContainerTestCase;

final class ContainerTest extends ContainerTestCase
{
	public function testContainerBootsInTestEnvironment(): void
	{
		$container = $this->getContainer();

		self::assertSame('test', $_ENV['APP_ENV']);
		self::assertInstanceOf(StringService::class, $container->getByType(StringService::class));
		self::assertInstanceOf(RouteList::class, $container->getByType(RouteList::class));
	}
}
