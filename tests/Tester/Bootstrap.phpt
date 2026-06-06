<?php declare(strict_types=1);

use App\Bootstrap;
use Nette\Application\Routers\RouteList;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

test('DI container boots with router service in test environment', function (): void {
	$container = Bootstrap::boot()->createContainer();

	Assert::same('test', $_ENV['APP_ENV']);
	Assert::type(RouteList::class, $container->getByType(RouteList::class));
});
