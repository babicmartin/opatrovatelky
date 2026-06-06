<?php declare(strict_types=1);

namespace Tests\Api;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\SearchRepository;
use App\Model\Table\FamilyTableMap;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
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

	/**
	 * @return array<string, mixed>
	 */
	private function dispatchSearch(int $type, string $term): array
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

		$presenter = $container->getByType(IPresenterFactory::class)->createPresenter('Admin:Home');
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		$response = $presenter->run(new ApplicationRequest('Admin:Home', 'GET', [
			'action' => 'default',
			Presenter::SignalKey => 'search-search',
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
