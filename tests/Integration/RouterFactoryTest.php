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

	public function testPartnerFamilyRoutesMatchOwnPresenter(): void
	{
		$router = RouterFactory::createRouter();

		$list = $router->match(new HttpRequest(new UrlScript('http://opatrovatelky.test/partner-family/default/73', '/')));
		self::assertNotNull($list);
		self::assertSame('Admin:PartnerFamily', $list['presenter']);
		self::assertSame('default', $list['action']);
		self::assertSame('73', (string) $list['id']);

		$dashboard = $router->match(new HttpRequest(new UrlScript('http://opatrovatelky.test/partner-family/babysitters/73?type=active&family=1129', '/')));
		self::assertNotNull($dashboard);
		self::assertSame('Admin:PartnerFamily', $dashboard['presenter']);
		self::assertSame('babysitters', $dashboard['action']);
		self::assertSame('active', $dashboard['type']);
		self::assertSame('1129', (string) $dashboard['family']);

		$partner = $router->match(new HttpRequest(new UrlScript('http://opatrovatelky.test/partner/update/73', '/')));
		self::assertNotNull($partner);
		self::assertSame('Admin:Partner', $partner['presenter']);
	}
}
