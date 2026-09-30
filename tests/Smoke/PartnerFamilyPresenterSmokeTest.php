<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\Turnus\TurnusStatus;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use Nette\Application\BadRequestException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

final class PartnerFamilyPresenterSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	private const string PRESENTER = 'Admin:PartnerFamily';

	private int $partnerId;

	private int $familyId;


	protected function setUp(): void
	{
		parent::setUp();

		TestDatabase::insert(StatusTurnusTableMap::TABLE_NAME, ['id' => TurnusStatus::STORNO, 'status' => 'Storno', 'color' => '#dc3545']);
		$this->partnerId = TestDatabase::createPartner(['name' => 'Smoke partner']);
		$this->familyId = TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Smokeova', FamilyTableMap::COL_PARTNER_ID => $this->partnerId]);
		$babysitterId = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Zuzana', OpatrovatelkaTableMap::COL_SURNAME => 'Opatrovatelska']);
		$stornoBabysitterId = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Stornova']);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $this->familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_DATE_FROM => '2020-01-01',
			TurnusTableMap::COL_DATE_TO => '2020-01-31',
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $this->familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $stornoBabysitterId,
			TurnusTableMap::COL_STATUS => TurnusStatus::STORNO,
		]);
	}


	public function testPartnerUpdateContainsFamilyListButton(): void
	{
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter('Admin:Partner', ['action' => 'update', 'id' => $this->partnerId]);

		self::assertStringContainsString('Zoznam rodín</a>', $html);
		self::assertStringContainsString('partner-family/default/' . $this->partnerId, $html);
	}


	public function testDefaultRendersFamilyCounts(): void
	{
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter(self::PRESENTER, ['id' => $this->partnerId]);

		self::assertStringContainsString('Partner Smoke partner – zoznam rodín', $html);
		self::assertStringContainsString('Smokeova', $html);
		self::assertStringContainsString('Spolu za partnera', $html);
		self::assertStringContainsString('tableone', $html);
		self::assertMatchesRegularExpression('~babysitters/' . $this->partnerId . '\?type=finished(&amp;|&)family=' . $this->familyId . '~', $html);
		self::assertStringNotContainsString('Stornova', $html);
	}


	/**
	 * @return iterable<string, array{string, bool}>
	 */
	public static function dashboardVariants(): iterable
	{
		yield 'all of family' => ['all', true];
		yield 'active of family' => ['active', true];
		yield 'finished of family' => ['finished', true];
		yield 'all of partner' => ['all', false];
		yield 'finished of partner' => ['finished', false];
	}


	#[DataProvider('dashboardVariants')]
	public function testBabysittersDashboardRenders(string $type, bool $withFamily): void
	{
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter(self::PRESENTER, [
			'action' => 'babysitters',
			'id' => $this->partnerId,
			'type' => $type,
			'family' => $withFamily ? $this->familyId : null,
		]);

		self::assertStringContainsString('</html>', $html);
		self::assertStringContainsString('Partner Smoke partner', $html);
		self::assertStringContainsString($withFamily ? 'sily – rodina Smokeova Maria' : 'sily – všetky rodiny', $html);
		self::assertStringContainsString('pf-kpis', $html);
		self::assertStringContainsString('Zoznam rodín partnera', $html);
		self::assertStringNotContainsString('Detail rodiny', $html);
		self::assertStringNotContainsString('Priemerný turnus', $html);
		self::assertStringNotContainsString('Pozícia', $html);
		self::assertStringNotContainsString('Stornova', $html);
		if ($type === 'active') {
			self::assertStringContainsString('Žiadne opatrovateľky', $html);
		} else {
			self::assertStringContainsString('Zuzana Opatrovatelska', $html);
			self::assertStringContainsString('1.1.2020', $html);
			self::assertStringContainsString('data-bs-toggle="collapse"', $html);
			self::assertStringContainsString('Turnusy – Zuzana Opatrovatelska', $html);
		}
	}


	/**
	 * @return iterable<string, array{array<string, mixed>}>
	 */
	public static function notFoundRequests(): iterable
	{
		yield 'unknown partner list' => [['action' => 'default', 'id' => 999999]];
		yield 'unknown partner dashboard' => [['action' => 'babysitters', 'id' => 999999]];
		yield 'invalid type' => [['action' => 'babysitters', 'type' => 'storno']];
		yield 'foreign family' => [['action' => 'babysitters', 'family' => 999999]];
	}


	/**
	 * @param array<string, mixed> $params
	 */
	#[DataProvider('notFoundRequests')]
	public function testInvalidRequestsReturn404(array $params): void
	{
		$this->loginAs(UserRole::ADMIN);

		try {
			$this->runPresenter(self::PRESENTER, $params + ['id' => $this->partnerId]);
			self::fail('Request must end with 404.');
		} catch (BadRequestException $exception) {
			self::assertSame(404, $exception->getCode());
		}
	}


	public function testCeoRoleCanOpenOverview(): void
	{
		$this->loginAs(UserRole::CEO);

		$html = $this->renderPresenter(self::PRESENTER, ['id' => $this->partnerId]);

		self::assertStringContainsString('Smokeova', $html);
	}


	public function testDealerRoleIsForbidden(): void
	{
		$this->loginAs(UserRole::DEALER);

		try {
			$this->runPresenter(self::PRESENTER, ['id' => $this->partnerId]);
			self::fail('Dealer must not access partner families.');
		} catch (BadRequestException $exception) {
			self::assertSame(403, $exception->getCode());
		}
	}


	protected function tearDown(): void
	{
		$this->logout();

		parent::tearDown();
	}
}
