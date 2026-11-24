<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\PartnerTableMap;
use Dom\HTMLDocument;
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

	public function testRoleWithoutModuleAccessGetsNeitherPanelsNorIcons(): void
	{
		// The test database grants DEALER only the homepage.
		$this->loginAs(UserRole::DEALER);

		$source = $this->renderPresenter('Admin:Home');
		$html = HTMLDocument::createFromString($source, LIBXML_NOERROR, 'UTF-8');

		foreach (self::Panels as $panelId => $record) {
			self::assertNull($html->getElementById($panelId), $panelId);
			self::assertNull($html->querySelector('[data-bs-target="#' . $panelId . '"]'), $panelId);
			self::assertStringNotContainsString($record, $source, $panelId);
		}

		// Toolbar links that are not side panels stay as they were.
		self::assertNotNull($html->querySelector('a.admin-toolbar-icon[aria-label="Turnusy"]'));
	}
}
