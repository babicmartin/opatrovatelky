<?php declare(strict_types=1);

namespace Tests\Api;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
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
 * HTTP contract for the shared `handleAutosavePartial` signal (AdminPresenter).
 *
 * The field-update logic is covered by AutosaveFieldUpdateServiceTest. This suite
 * pins the presenter-level contract instead: same-site enforcement, ajax/POST guard,
 * allowed-context allow-list, entity ownership and the JSON success/error envelope.
 */
final class AutosavePartialContractTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	private const Presenter = 'Admin:Babysitter';

	public function testValidRequestSavesFieldAndReturnsSuccess(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Stable',
		]);

		$response = $this->dispatchAutosave([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		], ['id' => $babysitterId]);

		self::assertSame(['success' => true], $response->getPayload());
		self::assertSame(200, $this->responseCode());

		$babysitter = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId);
		self::assertNotNull($babysitter);
		self::assertSame('Eva', $babysitter->{OpatrovatelkaTableMap::COL_NAME});
		self::assertSame('Stable', $babysitter->{OpatrovatelkaTableMap::COL_SURNAME});
		self::assertSame(1, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
	}

	/**
	 * Nette enforces sameOrigin on every signal (Application\UI\AccessPolicy), so a
	 * cross-site autosave is intercepted by the framework CSRF guard and redirected
	 * before the handler runs. The contract that matters here is: no field is saved.
	 */
	public function testCrossSiteRequestIsBlockedByCsrfGuard(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Anna']);

		$response = $this->dispatchAutosave([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		], ['id' => $babysitterId], sameSite: false);

		self::assertObjectHasProperty('redirect', (object) $response->getPayload());
		self::assertSame('Anna', $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId)?->{OpatrovatelkaTableMap::COL_NAME});
		self::assertSame(0, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
	}

	public function testNonAjaxRequestIsRejected(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter();

		$response = $this->dispatchAutosave([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		], ['id' => $babysitterId], ajax: false);

		self::assertSame(['success' => false, 'message' => 'Neplatný autosave request.'], $response->getPayload());
		self::assertSame(400, $this->responseCode());
	}

	public function testMissingContextIsRejected(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter();

		$response = $this->dispatchAutosave([
			'id' => (string) $babysitterId,
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		], ['id' => $babysitterId]);

		self::assertSame(['success' => false, 'message' => 'Chýba autosave kontext alebo ID.'], $response->getPayload());
		self::assertSame(400, $this->responseCode());
	}

	public function testDisallowedContextIsRejected(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter();

		$response = $this->dispatchAutosave([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'turnus.update',
			'__autosave_field' => 'fee',
			'__autosave_value' => '10',
		], ['id' => $babysitterId]);

		self::assertSame(['success' => false, 'message' => 'Autosave kontext nie je povolený.'], $response->getPayload());
		self::assertSame(403, $this->responseCode());
	}

	public function testEntityOwnershipMismatchIsRejected(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Anna']);

		$response = $this->dispatchAutosave([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		], ['id' => $babysitterId + 1]);

		self::assertSame(['success' => false, 'message' => 'Neplatný autosave záznam.'], $response->getPayload());
		self::assertSame(400, $this->responseCode());

		$babysitter = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId);
		self::assertNotNull($babysitter);
		self::assertSame('Anna', $babysitter->{OpatrovatelkaTableMap::COL_NAME});
	}

	public function testUnknownFieldReportsSaveFailure(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter();

		$response = $this->dispatchAutosave([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'does_not_exist',
			'__autosave_value' => 'Eva',
		], ['id' => $babysitterId]);

		self::assertSame(['success' => false, 'message' => 'Autosave hodnotu sa nepodarilo uložiť.'], $response->getPayload());
		self::assertSame(400, $this->responseCode());
	}

	public function testDocumentOwnedByDifferentEntityIsNotFound(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$fileId = TestDatabase::createFile([
			FileTableMap::COL_DIR => 'babysitters/1',
			FileTableMap::COL_NOTICE => 'old note',
		]);

		$response = $this->dispatchAutosave([
			'id' => (string) $fileId,
			'__autosave_context' => 'documents.babysitter',
			'__autosave_field' => 'notice',
			'__autosave_value' => 'tampered',
		], ['id' => 2]);

		self::assertSame(['success' => false, 'message' => 'Dokument neexistuje.'], $response->getPayload());
		self::assertSame(404, $this->responseCode());
		$file = $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($fileId);
		self::assertNotNull($file);
		self::assertSame('old note', $file->{FileTableMap::COL_NOTICE});
	}

	public function testDocumentOwnedByRouteEntityIsUpdated(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$fileId = TestDatabase::createFile([
			FileTableMap::COL_DIR => 'babysitters/5',
			FileTableMap::COL_NOTICE => 'old note',
		]);

		$response = $this->dispatchAutosave([
			'id' => (string) $fileId,
			'__autosave_context' => 'documents.babysitter',
			'__autosave_field' => 'notice',
			'__autosave_value' => 'new note',
		], ['id' => 5]);

		self::assertSame(['success' => true], $response->getPayload());
		self::assertSame(200, $this->responseCode());
		$file = $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($fileId);
		self::assertNotNull($file);
		self::assertSame('new note', $file->{FileTableMap::COL_NOTICE});
	}

	/**
	 * @param array<string, mixed> $post
	 * @param array<string, mixed> $routeParams
	 */
	private function dispatchAutosave(
		array $post,
		array $routeParams = [],
		bool $ajax = true,
		bool $sameSite = true,
		string $httpMethod = 'POST',
	): JsonResponse {
		$headers = $ajax ? ['X-Requested-With' => 'XMLHttpRequest'] : [];
		$cookies = $sameSite ? [Helpers::StrictCookieName => '1'] : [];
		$request = new HttpRequest(new UrlScript('https://opatrovatelky.local/'), $post, [], $cookies, $headers, $httpMethod);

		$container = $this->getContainer();
		$container->removeService('http.request');
		$container->addService('http.request', $request);
		$container->getByType(IResponse::class)->setCode(IResponse::S200_OK);

		$presenter = $container->getByType(IPresenterFactory::class)->createPresenter(self::Presenter);
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		$params = $routeParams + ['action' => 'default', Presenter::SignalKey => 'autosavePartial'];
		$response = $presenter->run(new ApplicationRequest(self::Presenter, $httpMethod, $params));

		self::assertInstanceOf(JsonResponse::class, $response);

		return $response;
	}

	private function responseCode(): int
	{
		return $this->getContainer()->getByType(IResponse::class)->getCode();
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}
}
