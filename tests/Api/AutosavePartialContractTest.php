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

	public function testHhsTabRendersDefaultsAndEscapesNotes(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$id = TestDatabase::createBabysitter(['hhs_additional_notes' => '<em>poznámka</em>']);
		$html = $this->renderPresenter(self::Presenter, ['action' => 'update', 'tab' => 'hhs', 'id' => $id]);
		self::assertStringContainsString('</html>', $html);
		self::assertStringContainsString('Partnerské údaje HHS', $html);
		self::assertStringContainsString('&lt;em&gt;poznámka&lt;/em&gt;', $html);
		$dom = \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
		$tabs = $dom->querySelectorAll('a.btn[href*="tab="]');
		self::assertSame('Partnerské údaje HHS', $tabs->item($tabs->length - 1)?->textContent);
		$form = $dom->querySelector('form[data-autosave-context="babysitter.hhs"]');
		self::assertNotNull($form);
		self::assertCount(8, $form->querySelectorAll('.js-autosave-control'));
		$columns = $form->querySelectorAll('.row > .col-lg-4');
		self::assertCount(3, $columns);
		self::assertNull($form->querySelector('h1, h2, h3, h4, h5, h6'));
		foreach ([
			['start_date', 'night_work', 'additional_notes'],
			['monthly_costs', 'travel_costs', 'holiday_supplement'],
			['created_by_name', 'contact_phone'],
		] as $index => $fields) {
			foreach ($fields as $field) {
				self::assertNotNull($columns->item($index)?->querySelector('[name="hhs_' . $field . '"]'));
			}
		}
		self::assertSame('8', $form->querySelector('textarea')?->getAttribute('rows'));
		foreach (['start_date' => '', 'monthly_costs' => '3500', 'travel_costs' => '340', 'holiday_supplement' => '100%/Tag', 'created_by_name' => 'Andrea Seyr', 'contact_phone' => '07134- 139 3 131'] as $field => $expected) {
			$control = $form->querySelector('input[name="hhs_' . $field . '"]');
			self::assertNotNull($control);
			self::assertEquals($expected, $control->getAttribute('value'));
		}
		self::assertNotNull($form->querySelector('select[name="hhs_night_work"]'));
		self::assertNotNull($form->querySelector('textarea[name="hhs_additional_notes"]'));
	}

	public function testHhsTabIsHiddenForWorkerAndRejectsWrites(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$id = TestDatabase::createBabysitter(['type' => 2]);
		$html = $this->renderPresenter(self::Presenter, ['action' => 'update', 'tab' => 'hhs', 'id' => $id]);
		self::assertStringNotContainsString('Partnerské údaje HHS', $html);
		self::assertStringNotContainsString('name="hhs_contact_phone"', $html);
		$response = $this->dispatchAutosave([
			'id' => (string) $id,
			'__autosave_context' => 'babysitter.hhs',
			'__autosave_field' => 'hhs_contact_phone',
			'__autosave_value' => 'other',
		], ['id' => $id]);
		self::assertFalse($response->getPayload()['success']);
		self::assertSame(0, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
	}

	public function testHhsRequiresLoginAndRejectsMismatchedCard(): void
	{
		$id = TestDatabase::createBabysitter();
		$this->logout();
		self::assertInstanceOf(\Nette\Application\Responses\RedirectResponse::class, $this->runPresenter(self::Presenter, ['action' => 'update', 'tab' => 'hhs', 'id' => $id]));
		$this->loginAs(UserRole::ADMIN);
		$response = $this->dispatchAutosave([
			'id' => (string) $id,
			'__autosave_context' => 'babysitter.hhs',
			'__autosave_field' => 'hhs_created_by_name',
			'__autosave_value' => 'Other',
		], ['id' => $id + 1]);
		self::assertFalse($response->getPayload()['success']);
		$row = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($id);
		self::assertNotNull($row);
		self::assertSame('Andrea Seyr', $row->hhs_created_by_name);
	}

	public function testHhsAllFieldsPersistIndependently(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$id = TestDatabase::createBabysitter(['phone' => 'original']);
		$values = [
			'hhs_start_date' => '2027-01-23',
			'hhs_night_work' => '0',
			'hhs_additional_notes' => "First\nSecond",
			'hhs_monthly_costs' => '3150.23',
			'hhs_travel_costs' => '350.42',
			'hhs_holiday_supplement' => '50%/Tag',
			'hhs_created_by_name' => 'New contact',
			'hhs_contact_phone' => '001-320',
		];
		foreach ($values as $field => $value) {
			$response = $this->dispatchAutosave([
				'id' => (string) $id,
				'__autosave_context' => 'babysitter.hhs',
				'__autosave_field' => $field,
				'__autosave_value' => $value,
			], ['id' => $id]);
			self::assertTrue($response->getPayload()['success'], $field);
		}
		$row = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($id);
		self::assertNotNull($row);
		foreach ($values as $field => $value) {
			$actual = $row->{$field};
			self::assertSame($value, $actual instanceof \DateTimeInterface ? $actual->format('Y-m-d') : (string) $actual);
		}
		self::assertSame('original', $row->phone);
	}

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
