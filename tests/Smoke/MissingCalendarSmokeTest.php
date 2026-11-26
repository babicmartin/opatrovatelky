<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\UserTableMap;
use Dom\HTMLDocument;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * The team absence calendar really renders (Latte included) and shows the right things to the right role.
 */
final class MissingCalendarSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	private const string Presenter = 'Admin:MissingRegistry';
	private const array November = ['action' => 'calendar', 'year' => 2026, 'month' => 11];

	/**
	 * @return iterable<string, array{UserRole}>
	 */
	public static function roles(): iterable
	{
		foreach (UserRole::cases() as $role) {
			yield $role->value => [$role];
		}
	}

	#[DataProvider('roles')]
	public function testCalendarRendersTheMonthForEveryRole(UserRole $role): void
	{
		$this->loginAs($role);
		$colleagueId = $this->createColleague();
		// Thu 5 Nov - Mon 9 Nov 2026: three working days in November, the weekend is shown but not counted.
		$this->insertRecord($colleagueId, '2026-11-05', '2026-11-09', MissingRegistryTableMap::COL_TYPE_DOVOLENKA, 'Dovolenka <b>pri mori</b>');

		$html = $this->renderPresenter(self::Presenter, self::November);
		$dom = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');

		self::assertStringContainsString('</html>', $html);
		self::assertSame('Tímový kalendár neprítomnosti', trim((string) $dom->querySelector('h1')?->textContent));
		self::assertSame('November 2026', trim((string) $dom->querySelector('.mc-nav-title')?->textContent));
		self::assertCount(30, $dom->querySelectorAll('.mc-sheet tbody tr'));
		self::assertCount(9, $dom->querySelectorAll('.mc-sheet tbody tr.is-weekend'));
		// 1 November 2026 is a Sunday and a day of rest; 17 November is a working day since 2025.
		self::assertCount(1, $dom->querySelectorAll('.mc-sheet tbody tr.is-holiday'));
		self::assertStringContainsString('Jana Kolegyňa', (string) $dom->querySelector('.mc-sheet thead')?->textContent);

		$entries = $dom->querySelectorAll('.mc-entry');
		self::assertCount(5, $entries);
		self::assertCount(2, $dom->querySelectorAll('.mc-entry.is-uncounted'));
		foreach ($entries as $entry) {
			self::assertStringContainsString('Dovolenka', (string) $entry->querySelector('.mc-entry-label')?->textContent);
		}
		self::assertCount(1, $dom->querySelectorAll('.mc-entry-note'));
		self::assertStringContainsString('Dovolenka &lt;b&gt;pri mori&lt;/b&gt;', $html);
		self::assertStringNotContainsString('<b>pri mori</b>', $html);

		$summary = (string) $dom->querySelector('.mc-summary tbody')?->textContent;
		self::assertMatchesRegularExpression('/Jana Kolegyňa.*\b3\b.*november: 3/su', $summary);

		// Only a role that manages all records may change a colleague's record or add one for a colleague.
		$managesAll = in_array($role, [UserRole::CEO, UserRole::ADMIN], true);
		self::assertCount($managesAll ? 5 : 0, $dom->querySelectorAll('button.mc-entry.js-mc-edit'));
		self::assertSame($managesAll, $dom->querySelector('.js-mc-entry-form [name="userId"]') !== null);
		self::assertSame(
			$managesAll,
			$dom->querySelector('.mc-cell[data-user-id="' . $colleagueId . '"] .js-mc-add') !== null,
		);
	}

	public function testUserAddsOnlyToTheOwnColumn(): void
	{
		$actorId = $this->loginAs(UserRole::DEALER_JUNIOR);
		$colleagueId = $this->createColleague();
		$this->insertRecord($actorId, '2026-11-10', '2026-11-10', MissingRegistryTableMap::COL_TYPE_LEKAR, '');

		$dom = HTMLDocument::createFromString($this->renderPresenter(self::Presenter, self::November), LIBXML_NOERROR, 'UTF-8');

		self::assertCount(30, $dom->querySelectorAll('.mc-cell[data-user-id="' . $actorId . '"] .js-mc-add'));
		self::assertCount(0, $dom->querySelectorAll('.mc-cell[data-user-id="' . $colleagueId . '"] .js-mc-add'));
		self::assertCount(1, $dom->querySelectorAll('button.mc-entry.js-mc-edit'));
	}

	public function testCalendarWithoutParametersShowsTheCurrentMonth(): void
	{
		$this->loginAs(UserRole::DEALER_JUNIOR);

		$dom = HTMLDocument::createFromString($this->renderPresenter(self::Presenter, ['action' => 'calendar']), LIBXML_NOERROR, 'UTF-8');

		self::assertCount((int) date('t'), $dom->querySelectorAll('.mc-sheet tbody tr'));
		self::assertCount(1, $dom->querySelectorAll('.mc-sheet tbody tr.is-today'));
	}

	/**
	 * @return iterable<string, array{array<string, mixed>}>
	 */
	public static function periodsThatDoNotExist(): iterable
	{
		yield 'month 13' => [['year' => 2026, 'month' => 13]];
		yield 'month 0' => [['year' => 2026, 'month' => 0]];
		yield 'year before the supported range' => [['year' => 1999, 'month' => 5]];
		yield 'year after the supported range' => [['year' => 2101, 'month' => 5]];
	}

	/**
	 * @param array<string, mixed> $period
	 */
	#[DataProvider('periodsThatDoNotExist')]
	public function testPeriodThatDoesNotExistIsNotFound(array $period): void
	{
		$this->loginAs(UserRole::ADMIN);

		try {
			$this->runPresenter(self::Presenter, ['action' => 'calendar'] + $period);
			self::fail('The calendar must refuse the period.');
		} catch (BadRequestException $exception) {
			self::assertSame(404, $exception->getCode());
		}
	}

	public function testMonthNavigationThroughFetchReturnsOnlyTheCalendarSnippet(): void
	{
		$this->loginAs(UserRole::DEALER_JUNIOR);
		$container = $this->getContainer();
		$container->removeService('http.request');
		$container->addService('http.request', new HttpRequest(
			new UrlScript('https://opatrovatelky.local/'),
			headers: ['X-Requested-With' => 'XMLHttpRequest'],
			method: 'GET',
		));

		try {
			$presenter = $container->getByType(IPresenterFactory::class)->createPresenter(self::Presenter);
			self::assertInstanceOf(Presenter::class, $presenter);
			$presenter->autoCanonicalize = false;
			$response = $presenter->run(new ApplicationRequest(self::Presenter, 'GET', ['action' => 'calendar', 'year' => 2026, 'month' => 2]));
		} finally {
			$container->removeService('http.request');
		}

		self::assertInstanceOf(JsonResponse::class, $response);
		$payload = (array) $response->getPayload();
		self::assertSame(['snippet-missingCalendar-content'], array_keys((array) ($payload['snippets'] ?? [])));
		$snippet = (string) ((array) $payload['snippets'])['snippet-missingCalendar-content'];
		self::assertStringContainsString('Február 2026', $snippet);
		self::assertStringNotContainsString('<html', $snippet);
	}

	public function testListAndCalendarLinkToEachOtherAndTheToolbarOpensTheCalendar(): void
	{
		$this->loginAs(UserRole::DEALER_JUNIOR);

		$list = HTMLDocument::createFromString($this->renderPresenter(self::Presenter), LIBXML_NOERROR, 'UTF-8');
		$calendar = HTMLDocument::createFromString($this->renderPresenter(self::Presenter, self::November), LIBXML_NOERROR, 'UTF-8');

		self::assertSame('/missing-registry/calendar', $list->querySelector('.admin-toolbar-icon[aria-label="Evidencia neprítomnosti"]')?->getAttribute('href'));
		self::assertNotNull($list->querySelector('a.btn[href="/missing-registry/calendar"]'));
		self::assertNotNull($calendar->querySelector('a.btn[href="/missing-registry"]'));
	}

	private function createColleague(): int
	{
		return TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Jana',
			UserTableMap::COL_SECOND_NAME => 'Kolegyňa',
			UserTableMap::COL_EMAIL => 'jana.kolegyna@example.test',
			UserTableMap::COL_PERMISSION => UserRole::DEALER_JUNIOR->getPermissionId(),
		]);
	}

	private function insertRecord(int $userId, string $from, string $to, string $typeColumn, string $notice): int
	{
		return TestDatabase::insert(MissingRegistryTableMap::TABLE_NAME, [
			MissingRegistryTableMap::COL_USER_ID => $userId,
			MissingRegistryTableMap::COL_DATE_FROM => $from,
			MissingRegistryTableMap::COL_DATE_TO => $to,
			$typeColumn => 1,
			MissingRegistryTableMap::COL_NOTICE => $notice,
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);
	}
}
