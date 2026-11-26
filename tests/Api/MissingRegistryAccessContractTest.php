<?php declare(strict_types=1);

namespace Tests\Api;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\UserTableMap;
use Dom\HTMLDocument;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\RedirectResponse;
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
 * Who may change which record of the absence registry, on every way in:
 * autosave, the row form and the delete form of the list, and the dialog of the team calendar.
 *
 * Everybody with the module sees all records. A record is changed by its owner; only a role with
 * Resource::MISSING_MANAGE_ALL (CEO, ADMIN) changes the records of others or hands a record to somebody else.
 * The tests send what a signed-in user can send from the browser console.
 */
final class MissingRegistryAccessContractTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	private const string Presenter = 'Admin:MissingRegistry';
	private const array CalendarParams = ['action' => 'calendar', 'year' => 2026, 'month' => 11];

	/**
	 * Actor, whose record ('own', 'foreign', 'nobody', 'deleted own'), field, value ('other': id of another user,
	 * 'admin': id of an administrator, otherwise the literal) and the expected HTTP status.
	 *
	 * @return iterable<string, array{UserRole, string, string, string, int}>
	 */
	public static function refusedAutosaves(): iterable
	{
		$junior = UserRole::DEALER_JUNIOR;

		yield 'junior changes the note of a colleague' => [$junior, 'foreign', 'notice', 'hacked', 403];
		yield 'junior ticks a type on a colleague\'s record' => [$junior, 'foreign', 'typeDovolenka', '1', 403];
		yield 'junior takes over a colleague\'s record' => [$junior, 'foreign', 'userId', 'self', 403];
		yield 'junior hands the own record to a colleague' => [$junior, 'own', 'userId', 'other', 403];
		yield 'junior changes a record of nobody' => [$junior, 'nobody', 'notice', 'hacked', 403];
		yield 'junior changes the own deleted record' => [$junior, 'deleted own', 'notice', 'hacked', 404];
		yield 'manager changes the note of a colleague' => [UserRole::DEALER, 'foreign', 'notice', 'hacked', 403];
		yield 'CEO hands a record to a user that does not exist' => [UserRole::CEO, 'foreign', 'userId', '999999', 403];
		yield 'CEO hands a record to an administrator' => [UserRole::CEO, 'foreign', 'userId', 'admin', 403];
		yield 'CEO sends a user id that is not a number' => [UserRole::CEO, 'foreign', 'userId', '4 OR 1=1', 403];
		yield 'CEO changes a deleted record' => [UserRole::CEO, 'deleted own', 'notice', 'hacked', 404];
	}

	#[DataProvider('refusedAutosaves')]
	public function testAutosaveRefusesWhatTheRoleMayNotChange(UserRole $role, string $owner, string $field, string $value, int $status): void
	{
		$actorId = $this->loginAs($role);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$adminId = $this->createUser(UserRole::ADMIN, 'admin');
		$recordId = match ($owner) {
			'own' => $this->insertRecord($actorId),
			'foreign' => $this->insertRecord($otherId),
			'nobody' => $this->insertRecord(0),
			'deleted own' => $this->insertRecord($actorId, [MissingRegistryTableMap::COL_DELETED => 1]),
			default => self::fail('Unknown owner ' . $owner),
		};
		$value = match ($value) {
			'self' => (string) $actorId,
			'other' => (string) $otherId,
			'admin' => (string) $adminId,
			default => $value,
		};
		$before = $this->readRecord($recordId);

		[$code, $payload] = $this->autosave($recordId, $field, $value);

		self::assertSame($status, $code);
		self::assertFalse($payload['success'] ?? null);
		self::assertSame($before, $this->readRecord($recordId));
		self::assertSame(0, $this->countChangeLog());
	}

	public function testOwnerAutosavesTheOwnRecord(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$recordId = $this->insertRecord($actorId);

		[$code, $payload] = $this->autosave($recordId, 'notice', 'Zmenená poznámka');

		self::assertSame(200, $code);
		self::assertSame(['success' => true], $payload);
		self::assertSame('Zmenená poznámka', $this->readRecord($recordId)[MissingRegistryTableMap::COL_NOTICE]);
		self::assertSame(1, $this->countChangeLog());
	}

	public function testRoleThatManagesAllHandsARecordToAnotherPerson(): void
	{
		$this->loginAs(UserRole::CEO);
		$firstId = $this->createUser(UserRole::DEALER_JUNIOR, 'first');
		$secondId = $this->createUser(UserRole::DEALER, 'second');
		$recordId = $this->insertRecord($firstId);

		[$code, $payload] = $this->autosave($recordId, 'userId', (string) $secondId);

		self::assertSame(200, $code);
		self::assertSame(['success' => true], $payload);
		self::assertSame($secondId, $this->readRecord($recordId)[MissingRegistryTableMap::COL_USER_ID]);
	}

	public function testRowFormWritesToItsOwnRecordWhateverTheHiddenIdSays(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$ownId = $this->insertRecord($actorId);
		$foreignId = $this->insertRecord($otherId);
		$foreignBefore = $this->readRecord($foreignId);
		$form = $this->readForm($this->renderPresenter(self::Presenter), 'frm-missingRegistryList-registryForm-' . $ownId);

		$response = $this->send('POST', ['action' => 'default'], $form + [
			'id' => (string) $foreignId,
			'userId' => (string) $otherId,
			'dateFrom' => '03.11.2026',
			'dateTo' => '04.11.2026',
			'typePn' => 'on',
			'notice' => 'Zmena cez formulár',
			'save' => 'Uložiť',
		], ajax: false);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($foreignBefore, $this->readRecord($foreignId));
		$own = $this->readRecord($ownId);
		self::assertSame($actorId, $own[MissingRegistryTableMap::COL_USER_ID]);
		self::assertSame('2026-11-03', $own[MissingRegistryTableMap::COL_DATE_FROM]);
		self::assertSame('2026-11-04', $own[MissingRegistryTableMap::COL_DATE_TO]);
		self::assertSame(1, $own[MissingRegistryTableMap::COL_TYPE_PN]);
		self::assertSame(0, $own[MissingRegistryTableMap::COL_TYPE_DOVOLENKA]);
		self::assertSame('Zmena cez formulár', $own[MissingRegistryTableMap::COL_NOTICE]);
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function listFormsOfAForeignRecord(): iterable
	{
		yield 'row form' => ['registryForm'];
		yield 'delete form' => ['deleteRegistryForm'];
	}

	#[DataProvider('listFormsOfAForeignRecord')]
	public function testListFormOfAForeignRecordIsRefused(string $formName): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$ownId = $this->insertRecord($actorId);
		$foreignId = $this->insertRecord($otherId);
		$foreignBefore = $this->readRecord($foreignId);
		// A valid token of the user's own form, sent to the form of somebody else's record.
		$form = $this->readForm($this->renderPresenter(self::Presenter), 'frm-missingRegistryList-' . $formName . '-' . $ownId);
		$form['_do'] = 'missingRegistryList-' . $formName . '-' . $foreignId . '-submit';

		try {
			$this->send('POST', ['action' => 'default'], $form + ['notice' => 'hacked', 'delete' => 'Odstrániť', 'save' => 'Uložiť'], ajax: false);
			self::fail('The form of a foreign record must be refused.');
		} catch (BadRequestException $exception) {
			self::assertSame(403, $exception->getCode());
		}

		self::assertSame($foreignBefore, $this->readRecord($foreignId));
		self::assertSame(0, $this->readRecord($ownId)[MissingRegistryTableMap::COL_DELETED]);
		self::assertSame(0, $this->countChangeLog());
	}

	public function testOwnerDeletesTheOwnRecordInTheList(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$ownId = $this->insertRecord($actorId);
		$form = $this->readForm($this->renderPresenter(self::Presenter), 'frm-missingRegistryList-deleteRegistryForm-' . $ownId);

		$response = $this->send('POST', ['action' => 'default'], $form + ['delete' => 'Odstrániť'], ajax: false);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame(1, $this->readRecord($ownId)[MissingRegistryTableMap::COL_DELETED]);
		self::assertSame(1, $this->countChangeLog());
	}

	public function testListShowsForeignRecordsWithoutAFormAndWithoutTheOwnerSelect(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$ownId = $this->insertRecord($actorId);
		$foreignId = $this->insertRecord($otherId, [MissingRegistryTableMap::COL_NOTICE => 'Poznámka kolegu']);

		$dom = HTMLDocument::createFromString($this->renderPresenter(self::Presenter), LIBXML_NOERROR, 'UTF-8');

		$ownForm = $dom->getElementById('frm-missingRegistryList-registryForm-' . $ownId);
		self::assertNotNull($ownForm);
		self::assertNull($ownForm->querySelector('[name="userId"]'));
		self::assertNotNull($dom->getElementById('frm-missingRegistryList-deleteRegistryForm-' . $ownId));
		self::assertNull($dom->getElementById('frm-missingRegistryList-registryForm-' . $foreignId));
		self::assertNull($dom->getElementById('frm-missingRegistryList-deleteRegistryForm-' . $foreignId));
		self::assertStringContainsString('Poznámka kolegu', (string) $dom->querySelector('.missing-registry-readonly')?->textContent);
		// Each type is labelled once next to its checkbox.
		self::assertSame(1, substr_count((string) $ownForm->textContent, 'Dovolenka'));
		self::assertSame(1, substr_count((string) $ownForm->textContent, 'PN'));
	}

	/**
	 * @return iterable<string, array{UserRole, bool}>
	 */
	public static function newRowOwners(): iterable
	{
		yield 'junior gets the own row' => [UserRole::DEALER_JUNIOR, true];
		yield 'CEO gets a row of nobody and chooses the person' => [UserRole::CEO, false];
	}

	#[DataProvider('newRowOwners')]
	public function testNewRowOfTheListBelongsToTheUserWhoMayNotChooseThePerson(UserRole $role, bool $ownsTheRow): void
	{
		$actorId = $this->loginAs($role);
		$form = $this->readForm($this->renderPresenter(self::Presenter), 'frm-missingRegistryList-createMissingRegistryForm');

		$response = $this->send('POST', ['action' => 'default'], $form + ['create' => 'Pridať novú evidenciu'], ajax: false);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($ownsTheRow ? $actorId : 0, $this->readRecord($this->singleRecordId())[MissingRegistryTableMap::COL_USER_ID]);
	}

	public function testCalendarStoresANewRecordForTheUserWhateverPersonIsSent(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$form = $this->readForm($this->renderPresenter(self::Presenter, self::CalendarParams), 'frm-missingCalendar-entryForm');

		$response = $this->send('POST', self::CalendarParams, $form + [
			'id' => '0',
			'userId' => (string) $otherId,
			'dateFrom' => '2026-11-02',
			'dateTo' => '2026-11-06',
			'types' => ['dovolenka'],
			'notice' => 'Dovolenka z kalendára',
			'save' => '1',
		]);

		$payload = $this->payload($response);
		self::assertTrue($payload['success'] ?? null);
		self::assertStringContainsString('Dovolenka z kalendára', $payload['snippets']['snippet-missingCalendar-content'] ?? '');
		$row = $this->readRecord($this->singleRecordId());
		self::assertSame($actorId, $row[MissingRegistryTableMap::COL_USER_ID]);
		self::assertSame('2026-11-02', $row[MissingRegistryTableMap::COL_DATE_FROM]);
		self::assertSame('2026-11-06', $row[MissingRegistryTableMap::COL_DATE_TO]);
		self::assertSame(1, $row[MissingRegistryTableMap::COL_TYPE_DOVOLENKA]);
		self::assertSame(1, $this->countChangeLog());
	}

	public function testRoleThatManagesAllStoresARecordForAnotherPersonInTheCalendar(): void
	{
		$this->loginAs(UserRole::CEO);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$html = $this->renderPresenter(self::Presenter, self::CalendarParams);
		$form = $this->readForm($html, 'frm-missingCalendar-entryForm');

		$response = $this->send('POST', self::CalendarParams, $form + [
			'id' => '0',
			'userId' => (string) $otherId,
			'dateFrom' => '2026-11-07',
			'dateTo' => '',
			'types' => ['sluzba', 'zastup'],
			'notice' => '',
			'save' => '1',
		]);

		self::assertTrue($this->payload($response)['success'] ?? null);
		$row = $this->readRecord($this->singleRecordId());
		self::assertSame($otherId, $row[MissingRegistryTableMap::COL_USER_ID]);
		self::assertSame('2026-11-07', $row[MissingRegistryTableMap::COL_DATE_TO]);
		self::assertSame(1, $row[MissingRegistryTableMap::COL_TYPE_SLUZBA]);
		self::assertSame(1, $row[MissingRegistryTableMap::COL_TYPE_ZASTUP]);
	}

	/**
	 * Posted values that must leave the table empty, with a part of the error shown in the dialog.
	 *
	 * @return iterable<string, array{array<string, mixed>, string}>
	 */
	public static function invalidCalendarValues(): iterable
	{
		$valid = ['id' => '0', 'userId' => 'other', 'dateFrom' => '2026-11-02', 'dateTo' => '2026-11-03', 'types' => ['pn'], 'notice' => ''];

		yield 'end before start' => [['dateTo' => '2026-10-30'] + $valid, 'nemôže byť pred'];
		yield 'no type' => [['types' => []] + $valid, 'aspoň jeden typ'];
		yield 'unknown type' => [['types' => ['riaditel']] + $valid, 'aspoň jeden typ'];
		yield 'no start' => [['dateFrom' => ''] + $valid, 'Zadajte dátum'];
		yield 'date that does not exist' => [['dateFrom' => '2026-02-31'] + $valid, 'nie je platný'];
		yield 'range of several years' => [['dateTo' => '2029-11-03'] + $valid, 'najviac'];
		yield 'person that does not exist' => [['userId' => '999999'] + $valid, 'Vyberte'];
		yield 'administrator as the person' => [['userId' => 'admin'] + $valid, 'Vyberte'];
		yield 'record id that is not a number' => [['id' => '1 OR 1=1'] + $valid, 'Neplatný záznam'];
	}

	/**
	 * @param array<string, mixed> $values
	 */
	#[DataProvider('invalidCalendarValues')]
	public function testCalendarRefusesInvalidValues(array $values, string $error): void
	{
		$this->loginAs(UserRole::CEO);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$adminId = $this->createUser(UserRole::ADMIN, 'admin');
		$values['userId'] = match ($values['userId']) {
			'other' => (string) $otherId,
			'admin' => (string) $adminId,
			default => $values['userId'],
		};
		$form = $this->readForm($this->renderPresenter(self::Presenter, self::CalendarParams), 'frm-missingCalendar-entryForm');

		$payload = $this->payload($this->send('POST', self::CalendarParams, $form + $values + ['save' => '1']));

		self::assertFalse($payload['success'] ?? null);
		self::assertStringContainsString($error, implode(' | ', $payload['errors'] ?? []));
		self::assertSame(0, $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->count('*'));
		self::assertSame(0, $this->countChangeLog());
	}

	/**
	 * @return iterable<string, array{string, string, int}>
	 */
	public static function refusedCalendarChanges(): iterable
	{
		yield 'update of a colleague\'s record' => ['entryForm', 'foreign', 403];
		yield 'delete of a colleague\'s record' => ['deleteForm', 'foreign', 403];
		yield 'update of a deleted record' => ['entryForm', 'deleted own', 404];
		yield 'delete of a deleted record' => ['deleteForm', 'deleted own', 404];
		yield 'update of a record that does not exist' => ['entryForm', 'missing', 404];
		yield 'delete of a record that does not exist' => ['deleteForm', 'missing', 404];
	}

	#[DataProvider('refusedCalendarChanges')]
	public function testCalendarRefusesToChangeARecordTheUserMayNotChange(string $formName, string $owner, int $status): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$otherId = $this->createUser(UserRole::DEALER_JUNIOR, 'other');
		$recordId = match ($owner) {
			'foreign' => $this->insertRecord($otherId),
			'deleted own' => $this->insertRecord($actorId, [MissingRegistryTableMap::COL_DELETED => 1]),
			'missing' => 999999,
			default => self::fail('Unknown owner ' . $owner),
		};
		$before = $owner === 'missing' ? null : $this->readRecord($recordId);
		$form = $this->readForm($this->renderPresenter(self::Presenter, self::CalendarParams), 'frm-missingCalendar-' . $formName);

		try {
			$this->send('POST', self::CalendarParams, $form + [
				'id' => (string) $recordId,
				'dateFrom' => '2026-11-02',
				'dateTo' => '2026-11-03',
				'types' => ['pn'],
				'notice' => 'hacked',
				'save' => '1',
				'delete' => '1',
			]);
			self::fail('The change must be refused.');
		} catch (BadRequestException $exception) {
			self::assertSame($status, $exception->getCode());
		}

		if ($before !== null) {
			self::assertSame($before, $this->readRecord($recordId));
		}
		self::assertSame(0, $this->countChangeLog());
	}

	public function testOwnerChangesAndDeletesTheOwnRecordInTheCalendar(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$recordId = $this->insertRecord($actorId);
		$html = $this->renderPresenter(self::Presenter, self::CalendarParams);

		$updated = $this->payload($this->send('POST', self::CalendarParams, $this->readForm($html, 'frm-missingCalendar-entryForm') + [
			'id' => (string) $recordId,
			'dateFrom' => '2026-11-09',
			'dateTo' => '2026-11-10',
			'types' => ['lekar'],
			'notice' => 'Kontrola',
			'save' => '1',
		]));

		self::assertTrue($updated['success'] ?? null);
		$row = $this->readRecord($recordId);
		self::assertSame('2026-11-09', $row[MissingRegistryTableMap::COL_DATE_FROM]);
		self::assertSame(1, $row[MissingRegistryTableMap::COL_TYPE_LEKAR]);
		self::assertSame(0, $row[MissingRegistryTableMap::COL_TYPE_DOVOLENKA]);
		self::assertSame('Kontrola', $row[MissingRegistryTableMap::COL_NOTICE]);

		$deleted = $this->payload($this->send('POST', self::CalendarParams, $this->readForm($html, 'frm-missingCalendar-deleteForm') + [
			'id' => (string) $recordId,
			'delete' => '1',
		]));

		self::assertTrue($deleted['success'] ?? null);
		self::assertSame(1, $this->readRecord($recordId)[MissingRegistryTableMap::COL_DELETED]);
		self::assertSame(2, $this->countChangeLog());
	}

	/**
	 * @return iterable<string, array{bool, string}>
	 */
	public static function untrustedRequests(): iterable
	{
		yield 'cross-site POST' => [false, 'valid'];
		yield 'invalid token' => [true, 'invalid'];
	}

	#[DataProvider('untrustedRequests')]
	public function testCalendarFormIgnoresARequestThatDidNotComeFromItsOwnPage(bool $sameSite, string $token): void
	{
		$this->loginAs(UserRole::DEALER_JUNIOR);
		$form = $this->readForm($this->renderPresenter(self::Presenter, self::CalendarParams), 'frm-missingCalendar-entryForm');
		if ($token === 'invalid') {
			$form['_token_'] = 'invalid-token';
		}

		try {
			$this->send('POST', self::CalendarParams, $form + [
				'id' => '0',
				'dateFrom' => '2026-11-02',
				'types' => ['pn'],
				'notice' => '',
				'save' => '1',
			], sameSite: $sameSite);
		} catch (BadRequestException $exception) {
			self::assertSame(403, $exception->getCode());
		}

		self::assertSame(0, $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->count('*'));
	}

	public function testGuestChangesNothingAndIsSentToTheLoginPage(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$recordId = $this->insertRecord($actorId);
		$html = $this->renderPresenter(self::Presenter, self::CalendarParams);
		$entryForm = $this->readForm($html, 'frm-missingCalendar-entryForm');
		$deleteForm = $this->readForm($html, 'frm-missingCalendar-deleteForm');
		$before = $this->readRecord($recordId);
		$this->logout();

		$requests = [
			'calendar save' => [self::CalendarParams, $entryForm + ['id' => (string) $recordId, 'dateFrom' => '2026-11-02', 'types' => ['pn'], 'notice' => 'hacked', 'save' => '1']],
			'calendar delete' => [self::CalendarParams, $deleteForm + ['id' => (string) $recordId, 'delete' => '1']],
			'day preview' => [self::CalendarParams + [Presenter::SignalKey => 'missingCalendar-preview', 'missingCalendar-from' => '2026-11-02'], []],
			'autosave' => [['action' => 'default', Presenter::SignalKey => 'autosavePartial'], [
				'id' => (string) $recordId,
				'__autosave_context' => 'missingRegistry.row',
				'__autosave_field' => 'notice',
				'__autosave_value' => 'hacked',
			]],
		];

		foreach ($requests as $name => [$params, $post]) {
			$payload = $this->payload($this->send($post === [] ? 'GET' : 'POST', $params, $post));

			self::assertArrayNotHasKey('success', $payload, $name);
			self::assertSame('/login/', parse_url((string) ($payload['redirect'] ?? ''), PHP_URL_PATH), $name);
		}

		self::assertSame($before, $this->readRecord($recordId));
		self::assertSame(0, $this->countChangeLog());
	}

	public function testPreviewCountsTheDaysOfARange(): void
	{
		$this->loginAs(UserRole::DEALER_JUNIOR);

		$counted = $this->payload($this->send('GET', self::CalendarParams + [
			Presenter::SignalKey => 'missingCalendar-preview',
			'missingCalendar-from' => '2026-04-03',
			'missingCalendar-to' => '2026-04-07',
		]));
		$reversed = $this->payload($this->send('GET', self::CalendarParams + [
			Presenter::SignalKey => 'missingCalendar-preview',
			'missingCalendar-from' => '2026-04-07',
			'missingCalendar-to' => '2026-04-03',
		]));

		self::assertSame([
			'success' => true,
			'calendarDays' => 5,
			'workingDays' => 1,
			'weekendDays' => 2,
			'holidays' => ['Veľký piatok', 'Veľkonočný pondelok'],
		], $counted);
		self::assertSame(['success' => false], $reversed);
		self::assertSame(0, $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->count('*'));
	}

	/**
	 * @return array{int, array<string, mixed>}
	 */
	private function autosave(int $recordId, string $field, string $value): array
	{
		$response = $this->send('POST', ['action' => 'default', Presenter::SignalKey => 'autosavePartial'], [
			'id' => (string) $recordId,
			'__autosave_context' => 'missingRegistry.row',
			'__autosave_field' => $field,
			'__autosave_value' => $value,
			'__autosave_checked' => $value,
		]);

		return [$this->getContainer()->getByType(IResponse::class)->getCode(), $this->payload($response)];
	}

	/**
	 * Runs the presenter with a request as the browser of a signed-in user would send it.
	 *
	 * @param array<string, mixed> $params
	 * @param array<string, mixed> $post
	 */
	private function send(string $method, array $params, array $post = [], bool $ajax = true, bool $sameSite = true): Response
	{
		$headers = ['Sec-Fetch-Site' => $sameSite ? 'same-origin' : 'cross-site'];
		if ($ajax) {
			$headers['X-Requested-With'] = 'XMLHttpRequest';
		}

		$container = $this->getContainer();
		$container->removeService('http.request');
		$container->addService('http.request', new HttpRequest(
			new UrlScript('https://opatrovatelky.local/'),
			$post,
			[],
			$sameSite ? [Helpers::StrictCookieName => '1'] : [],
			$headers,
			$method,
		));
		$container->getByType(IResponse::class)->setCode(IResponse::S200_OK);

		$presenter = $container->getByType(IPresenterFactory::class)->createPresenter(self::Presenter);
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		return $presenter->run(new ApplicationRequest(self::Presenter, $method, $params, $post));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function payload(Response $response): array
	{
		self::assertInstanceOf(JsonResponse::class, $response);

		return json_decode((string) json_encode($response->getPayload()), true, flags: JSON_THROW_ON_ERROR);
	}

	/**
	 * Hidden fields a browser sends with the form: the signal and the CSRF token.
	 *
	 * @return array{_do: string, _token_: string}
	 */
	private function readForm(string $html, string $formId): array
	{
		$form = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8')->getElementById($formId);
		self::assertNotNull($form, 'Missing form ' . $formId);
		$signal = $form->querySelector('input[name="_do"]')?->getAttribute('value');
		$token = $form->querySelector('input[name="_token_"]')?->getAttribute('value');
		self::assertIsString($signal);
		self::assertIsString($token);
		self::assertNotSame('', $token);

		return ['_do' => $signal, '_token_' => $token];
	}

	private function createUser(UserRole $role, string $name): int
	{
		return TestDatabase::createUser([
			UserTableMap::COL_NAME => ucfirst($name),
			UserTableMap::COL_SECOND_NAME => 'Tester',
			UserTableMap::COL_EMAIL => $name . '.registry@example.test',
			UserTableMap::COL_PERMISSION => $role->getPermissionId(),
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	private function insertRecord(int $userId, array $overrides = []): int
	{
		return TestDatabase::insert(MissingRegistryTableMap::TABLE_NAME, $overrides + [
			MissingRegistryTableMap::COL_USER_ID => $userId,
			MissingRegistryTableMap::COL_DATE_FROM => '2026-11-16',
			MissingRegistryTableMap::COL_DATE_TO => '2026-11-18',
			MissingRegistryTableMap::COL_TYPE_DOVOLENKA => 1,
			MissingRegistryTableMap::COL_NOTICE => 'Pôvodná poznámka',
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readRecord(int $id): array
	{
		$row = $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->get($id);
		self::assertNotNull($row);

		$values = [];
		foreach ($row->toArray() as $column => $value) {
			$values[$column] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
		}

		return $values;
	}

	/**
	 * Id of the only record in the table.
	 */
	private function singleRecordId(): int
	{
		$ids = $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->fetchPairs(null, MissingRegistryTableMap::COL_ID);
		self::assertCount(1, $ids);

		return (int) reset($ids);
	}

	private function countChangeLog(): int
	{
		return $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*');
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}
}
