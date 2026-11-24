<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Model\Enum\Settings\Environment;
use App\Model\Settings\EnvironmentFileLoader;
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

	public function testTestEnvironmentUsesOnlyTheCommittedTestValues(): void
	{
		$this->getContainer();
		$committed = EnvironmentFileLoader::read(dirname(__DIR__, 2) . '/config', Environment::TEST);

		self::assertSame($committed['MEDIA_COOKIE_SECRET'], $_ENV['MEDIA_COOKIE_SECRET']);
		// keys that exist only in the machine's config/.env must never reach a test run
		foreach (['DB_USER', 'DB_NAME', 'DB_PASSWORD', 'CRM_AI_USER', 'CRM_AI_PASSWORD'] as $key) {
			self::assertArrayNotHasKey($key, $_ENV, $key . ' leaked from config/.env into the test environment.');
		}
	}
}
