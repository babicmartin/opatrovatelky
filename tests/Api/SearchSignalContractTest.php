<?php declare(strict_types=1);

namespace Tests\Api;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\SearchRepository;
use App\Model\Table\FamilyTableMap;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * JSON contract for the shared Search component signal (`search-search`).
 * Pins the `{html: ...}` envelope and that type/term drive the rendered fragment.
 */
final class SearchSignalContractTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	public function testSearchSignalReturnsRenderedRowsForMatchingTerm(): void
	{
		$this->loginAs(UserRole::ADMIN);
		TestDatabase::createFamily([
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Hladana',
		]);

		$payload = $this->dispatchSearch(SearchRepository::TYPE_FAMILY, 'Hladana');

		self::assertArrayHasKey('html', $payload);
		self::assertStringContainsString('Výsledky vyhľadávania - rodiny', $payload['html']);
		self::assertStringContainsString('Hladana', $payload['html']);
	}

	public function testShortTermReturnsEmptyHtmlEnvelope(): void
	{
		$this->loginAs(UserRole::ADMIN);
		TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Hladana']);

		$payload = $this->dispatchSearch(SearchRepository::TYPE_FAMILY, 'ab');

		self::assertArrayHasKey('html', $payload);
		self::assertIsString($payload['html']);
		self::assertSame('', trim($payload['html']));
	}

	public function testMobileSearchWithoutTypeReturnsEveryTypeForAdmin(): void
	{
		$this->loginAs(UserRole::ADMIN);
		TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Hladana']);

		$payload = $this->dispatchSearch(null, 'Hladana', 'Admin:Home', 'search-searchMobile');

		self::assertIsArray($payload['counts']);
		self::assertSame([
			SearchRepository::TYPE_BABYSITTER,
			SearchRepository::TYPE_FAMILY,
			SearchRepository::TYPE_FAMILY_CONTACT,
			SearchRepository::TYPE_PARTNER,
			SearchRepository::TYPE_AGENCY,
		], array_keys($payload['counts']));
		self::assertSame(1, $payload['counts'][SearchRepository::TYPE_FAMILY]);
		self::assertStringContainsString('Hladana', $payload['html']);
	}

	#[DataProvider('provideSearchSignals')]
	public function testGuestOnLoginPageGetsNoSearchResults(string $signal, ?int $type): void
	{
		TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Hladana']);

		$this->assertSearchForbidden($type, 'Hladana', 'Login:Login', $signal);
	}

	#[DataProvider('provideSearchSignals')]
	public function testRoleWithoutModuleAccessGetsNoSearchResults(string $signal, ?int $type): void
	{
		// The test database grants DEALER only the homepage, so no searchable module is open to it.
		$this->loginAs(UserRole::DEALER);
		TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Hladana']);

		$this->assertSearchForbidden($type, 'Hladana', 'Admin:Home', $signal);
	}

	/**
	 * @return iterable<string, array{string, ?int}>
	 */
	public static function provideSearchSignals(): iterable
	{
		yield 'desktop search' => ['search-search', SearchRepository::TYPE_FAMILY];
		yield 'mobile search of one type' => ['search-searchMobile', SearchRepository::TYPE_FAMILY];
		yield 'mobile search of all types' => ['search-searchMobile', null];
	}

	private function assertSearchForbidden(?int $type, string $term, string $presenterName, string $signal): void
	{
		try {
			$this->dispatchSearch($type, $term, $presenterName, $signal);
		} catch (BadRequestException $exception) {
			self::assertSame(IResponse::S403_Forbidden, $exception->getHttpCode());

			return;
		}

		self::fail('Search signal ' . $signal . ' on ' . $presenterName . ' must be rejected.');
	}

	/**
	 * @return array<string, mixed>
	 */
	private function dispatchSearch(
		?int $type,
		string $term,
		string $presenterName = 'Admin:Home',
		string $signal = 'search-search',
	): array
	{
		$request = new HttpRequest(
			new UrlScript('https://opatrovatelky.local/'),
			[],
			[],
			[Helpers::StrictCookieName => '1'],
			['X-Requested-With' => 'XMLHttpRequest'],
			'GET',
		);

		$container = $this->getContainer();
		$container->removeService('http.request');
		$container->addService('http.request', $request);
		$container->getByType(IResponse::class)->setCode(IResponse::S200_OK);

		$presenter = $container->getByType(IPresenterFactory::class)->createPresenter($presenterName);
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		$response = $presenter->run(new ApplicationRequest($presenterName, 'GET', [
			'action' => 'default',
			Presenter::SignalKey => $signal,
			'search-type' => $type,
			'search-term' => $term,
		]));

		self::assertInstanceOf(JsonResponse::class, $response);
		$payload = $response->getPayload();
		self::assertIsArray($payload);

		return $payload;
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}
}
