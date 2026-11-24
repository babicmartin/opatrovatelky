<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use Nette\Application\BadRequestException;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

final class AdminPresenterSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	public function testSettingsPageRendersChangeLogLinkForCeo(): void
	{
		$this->loginAs(UserRole::CEO);

		$html = $this->renderPresenter('Admin:Settings');

		self::assertStringContainsString('Nastavenia', $html);
		self::assertStringContainsString('Evidencia zmien', $html);
		self::assertStringContainsString('Evidencia prihlásení', $html);
		self::assertStringContainsString('class="form-control search"', $html);
	}

	public function testChangeLogPageRendersSeededAuditRow(): void
	{
		$userId = TestDatabase::createUser([
			'name' => 'Audit',
			'second_name' => 'User',
			'acronym' => 'AU',
			'email' => 'audit.user@example.test',
			'permission' => 5,
		]);
		$entityId = TestDatabase::createBabysitter([
			'client_number' => 'A-600',
			'name' => 'Audit',
			'surname' => 'Smoke',
		]);
		TestDatabase::createChangeLog([
			ChangeLogTableMap::COL_CONTEXT => 'babysitter.main',
			ChangeLogTableMap::COL_ENTITY_TABLE => OpatrovatelkaTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => $entityId,
			ChangeLogTableMap::COL_FIELD_NAME => 'notice',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Poznámka',
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => 'stará hodnota',
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'nová hodnota',
			ChangeLogTableMap::COL_USER_ID => $userId,
			ChangeLogTableMap::COL_METADATA => '{"action":"updated"}',
		]);
		$this->loginAs(UserRole::CEO, $userId);

		$html = $this->renderPresenter('Admin:ChangeLog');

		self::assertStringContainsString('Evidencia zmien', $html);
		self::assertStringContainsString('Audit uložených polí', $html);
		self::assertStringContainsString('Poznámka', $html);
		self::assertStringContainsString('nová hodnota', $html);
		self::assertStringContainsString('Audit User', $html);
	}

	public function testDealerCannotOpenChangeLogPage(): void
	{
		$this->loginAs(UserRole::DEALER);

		try {
			$this->runPresenter('Admin:ChangeLog');
			self::fail('Dealer role must not open the change log presenter.');
		} catch (BadRequestException $exception) {
			self::assertSame(403, $exception->getCode());
		}
	}

	public function testLoginLogPageRendersForCeo(): void
	{
		$this->loginAs(UserRole::CEO);

		$html = $this->renderPresenter('Admin:LoginLog');

		self::assertStringContainsString('Evidencia prihlásení', $html);
		self::assertStringContainsString('Prihlásenia do administrácie', $html);
	}

	public function testLoginLogPageShowsRateLimitOutage(): void
	{
		$this->loginAs(UserRole::CEO);
		TestDatabase::insert('security_audit_log', [
			'event_type' => 'login_limit_unavailable',
			'email' => 'outage@example.test',
			'ip_address' => '203.0.113.10',
		]);

		$html = \Dom\HTMLDocument::createFromString($this->renderPresenter('Admin:LoginLog'), LIBXML_NOERROR, 'UTF-8');

		$badge = $html->querySelector('table tbody tr td span.badge');
		self::assertNotNull($badge);
		self::assertSame('Limit nedostupný', trim((string) $badge->textContent));
		self::assertSame('badge bg-dark', $badge->getAttribute('class'));
		self::assertStringContainsString('outage@example.test', (string) $html->querySelector('table tbody tr')?->textContent);
		self::assertNotNull($html->querySelector('select option[value="login_limit_unavailable"]'));
	}

	protected function tearDown(): void
	{
		$this->logout();

		parent::tearDown();
	}
}
