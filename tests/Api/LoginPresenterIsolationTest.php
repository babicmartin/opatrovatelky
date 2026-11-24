<?php declare(strict_types=1);

namespace Tests\Api;

use App\UI\Admin\AdminPresenter;
use App\UI\Login\Login\LoginPresenter;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\UI\BadSignalException;
use Nette\Application\UI\Presenter;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * The login page is the only page a guest can open, so it must not carry
 * any component or signal of the administration.
 */
final class LoginPresenterIsolationTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	/** The logout form is posted to the login presenter, so it is the one shared component. */
	private const array SharedComponents = ['logoutForm'];

	public function testLoginPresenterDoesNotInheritTheAdministration(): void
	{
		$parents = array_values(class_parents($this->createLoginPresenter()));

		self::assertNotContains(AdminPresenter::class, $parents);
		self::assertSame(Presenter::class, $parents[0] ?? null);
	}

	public function testLoginPresenterHasNoAdministrationComponent(): void
	{
		$adminComponents = $this->componentNames(AdminPresenter::class);
		self::assertContains('search', $adminComponents);
		self::assertContains('menu', $adminComponents);

		$loginComponents = $this->componentNames(LoginPresenter::class);
		sort($loginComponents);
		self::assertSame(['login', 'logoutForm'], $loginComponents);

		$presenter = $this->createLoginPresenter();
		foreach (array_diff($adminComponents, self::SharedComponents) as $name) {
			self::assertNull($presenter->getComponent($name, false), 'Component ' . $name . ' must not exist on the login page.');
		}
	}

	#[DataProvider('provideAdministrationSignals')]
	public function testGuestCannotCallAdministrationSignalOnLoginPage(string $signal, string $httpMethod): void
	{
		$request = new HttpRequest(
			new UrlScript('https://opatrovatelky.local/login/'),
			['id' => '1', '__autosave_context' => 'babysitter.main', '__autosave_field' => 'name', '__autosave_value' => 'x'],
			[],
			[Helpers::StrictCookieName => '1'],
			['X-Requested-With' => 'XMLHttpRequest'],
			$httpMethod,
		);
		$container = $this->getContainer();
		$container->removeService('http.request');
		$container->addService('http.request', $request);
		$container->getByType(IResponse::class)->setCode(IResponse::S200_OK);

		$presenter = $this->createLoginPresenter();

		try {
			$presenter->run(new ApplicationRequest('Login:Login', $httpMethod, [
				'action' => 'default',
				Presenter::SignalKey => $signal,
			]));
		} catch (BadRequestException $exception) {
			self::assertInstanceOf(BadSignalException::class, $exception, $exception->getMessage());
			self::assertSame(IResponse::S403_Forbidden, $exception->getHttpCode());

			return;
		}

		self::fail('Signal ' . $signal . ' must not exist on the login page.');
	}

	/**
	 * @return iterable<string, array{string, string}>
	 */
	public static function provideAdministrationSignals(): iterable
	{
		yield 'autosave' => ['autosavePartial', 'POST'];
		yield 'search' => ['search-search', 'GET'];
		yield 'mobile search' => ['search-searchMobile', 'GET'];
		yield 'profile image' => ['userProfileImage-upload', 'POST'];
	}

	/**
	 * @param class-string $class
	 * @return list<string>
	 */
	private function componentNames(string $class): array
	{
		$names = [];
		foreach (new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
			if (str_starts_with($method->getName(), 'createComponent') && $method->getName() !== 'createComponent') {
				$names[] = lcfirst(substr($method->getName(), strlen('createComponent')));
			}
		}

		return $names;
	}

	private function createLoginPresenter(): Presenter
	{
		$presenter = $this->getContainer()->getByType(IPresenterFactory::class)->createPresenter('Login:Login');
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		return $presenter;
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}
}
