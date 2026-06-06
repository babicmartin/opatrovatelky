<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Router\RouterFactory;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Tests\Support\PHPUnit\TestCase;

final class RouterFactoryTest extends TestCase
{
	public function testLoginRouteMatchesLoginPresenter(): void
	{
		$request = RouterFactory::createRouter()->match(new HttpRequest(new UrlScript('http://opatrovatelky.test/login/', '/')));

		self::assertNotNull($request);
		self::assertSame('Login:Login', $request['presenter']);
		self::assertSame('default', $request['action']);
	}

	public function testAdminEntityRouteMatchesPresenterActionAndId(): void
	{
		$request = RouterFactory::createRouter()->match(new HttpRequest(new UrlScript('http://opatrovatelky.test/babysitter/update/12', '/')));

		self::assertNotNull($request);
		self::assertSame('Admin:Babysitter', $request['presenter']);
		self::assertSame('update', $request['action']);
		self::assertSame('12', (string) $request['id']);
	}
}
