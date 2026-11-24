<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Security\Authorizator\AuthorizatorFactory;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\PartnerTableMap;
use Dom\HTMLDocument;
use Nette\Security\Authorizator;
use Nette\Security\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * The three side panels of the toolbar sit in the layout of every page.
 * A role without the module permission must get neither their data nor their icon.
 */
final class OffcanvasAccessSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	/** Values of FamilyRepository::TYPE_FAMILY and TYPE_PROJECT, which are private. */
	private const int TypeFamily = 1;
	private const int TypeProject = 2;

	private const array Panels = [
		'familyStatsOffcanvas' => 'Offcanvasrodina',
		'partnerStatsOffcanvas' => 'Offcanvaspartner',
		'projectStatsOffcanvas' => 'Offcanvasprojekt',
	];

	private ?Authorizator $originalAuthorizator = null;

	protected function setUp(): void
	{
		parent::setUp();

		TestDatabase::createFamily([
			FamilyTableMap::COL_SURNAME => 'Offcanvasrodina',
			FamilyTableMap::COL_TYPE => self::TypeFamily,
		]);
		TestDatabase::createFamily([
			FamilyTableMap::COL_SURNAME => 'Offcanvasprojekt',
			FamilyTableMap::COL_TYPE => self::TypeProject,
		]);
		TestDatabase::createPartner([PartnerTableMap::COL_NAME => 'Offcanvaspartner']);
	}

	protected function tearDown(): void
	{
		$this->logout();
		if ($this->originalAuthorizator !== null) {
			// The container is shared by all tests; a replaced authorizator must not leak out of this one.
			$this->getContainer()->getByType(User::class)->setAuthorizator($this->originalAuthorizator);
			$this->originalAuthorizator = null;
		}

		parent::tearDown();
	}

	public function testRoleWithModuleAccessGetsPanelsWithDataAndIcons(): void
	{
		$this->loginAs(UserRole::ADMIN);

		$html = HTMLDocument::createFromString($this->renderPresenter('Admin:Home'), LIBXML_NOERROR, 'UTF-8');

		foreach (self::Panels as $panelId => $record) {
			$panel = $html->getElementById($panelId);
			self::assertNotNull($panel, $panelId);
			self::assertStringContainsString($record, (string) $panel->textContent, $panelId);
			self::assertNotNull($html->querySelector('button[data-bs-target="#' . $panelId . '"]'), $panelId);
		}
	}

	public function testDealerJuniorGetsFamilyAndProjectPanelsButNotThePartnerOne(): void
	{
		// Partners start at DEALER; families and projects are open to every signed-in role.
		$this->loginAs(UserRole::DEALER_JUNIOR);

		$source = $this->renderPresenter('Admin:Home');

		$this->assertPanels($source, hidden: ['partnerStatsOffcanvas']);
	}

	/**
	 * No real role below ADMIN is denied families or projects, so their hiding cannot be shown with a real role.
	 * The layout is rendered with an authorizator that takes the module away from the role.
	 *
	 * @param list<string> $hidden
	 */
	#[DataProvider('provideDeniedModules')]
	public function testPanelAndIconFollowThePermissionOnTheModule(Resource $denied, array $hidden): void
	{
		$user = $this->getContainer()->getByType(User::class);
		$this->originalAuthorizator = $user->getAuthorizator();
		$acl = $this->getContainer()->getByType(AuthorizatorFactory::class)->create();
		$acl->deny(UserRole::DEALER->value, $denied->value);
		$user->setAuthorizator($acl);
		$this->loginAs(UserRole::DEALER);

		$source = $this->renderPresenter('Admin:Home');

		$this->assertPanels($source, $hidden);
	}

	/**
	 * @return iterable<string, array{Resource, list<string>}>
	 */
	public static function provideDeniedModules(): iterable
	{
		yield 'family' => [Resource::FAMILY, ['familyStatsOffcanvas']];
		yield 'project' => [Resource::PROJECT, ['projectStatsOffcanvas']];
		yield 'partner' => [Resource::PARTNER, ['partnerStatsOffcanvas']];
	}

	/**
	 * @param list<string> $hidden ids of the panels that must be missing; the others must be complete
	 */
	private function assertPanels(string $source, array $hidden): void
	{
		$html = HTMLDocument::createFromString($source, LIBXML_NOERROR, 'UTF-8');

		foreach (self::Panels as $panelId => $record) {
			$panel = $html->getElementById($panelId);
			$icon = $html->querySelector('[data-bs-target="#' . $panelId . '"]');

			if (in_array($panelId, $hidden, true)) {
				self::assertNull($panel, $panelId);
				self::assertNull($icon, $panelId);
				self::assertStringNotContainsString($record, $source, $panelId);
				continue;
			}

			self::assertNotNull($panel, $panelId);
			self::assertStringContainsString($record, (string) $panel->textContent, $panelId);
			self::assertNotNull($icon, $panelId);
		}

		// Toolbar links that are not side panels stay as they were.
		self::assertNotNull($html->querySelector('a.admin-toolbar-icon[aria-label="Turnusy"]'));
	}
}
