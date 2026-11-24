<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\PageRepository;
use App\Model\Table\PageTableMap;
use App\UI\Admin\Control\Layout\Menu\MenuPage;
use Dom\HTMLDocument;
use Nette\Application\LinkGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * The main menu: rows of `sany_pages` (the test database holds a copy of the production rows) give the names
 * and the order, the role's permission on the module decides which of them are shown.
 */
final class MenuAccessSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	/** Home link is written in the template and is not a row of the menu. */
	private const string HomeLink = 'Firma 4MS';

	/** Name of the menu row => presenter it opens. */
	private const array Destinations = [
		'Rodiny' => 'Admin:Family:default',
		'Opatrovateľky' => 'Admin:Babysitter:default',
		'Partneri' => 'Admin:Partner:default',
		'Agentúry' => 'Admin:Agency:default',
		'Pracovníci' => 'Admin:Worker:default',
		'Projekty' => 'Admin:Project:default',
		'Evidencia' => 'Admin:Turnus:default',
		'Nastavenia' => 'Admin:Settings:default',
		'Todo list' => 'Admin:Todo:default',
	];

	/**
	 * @return iterable<string, array{UserRole, list<string>}>
	 */
	public static function provideHiddenItems(): iterable
	{
		yield 'dealer junior' => [UserRole::DEALER_JUNIOR, ['Partneri', 'Agentúry', 'Nastavenia']];
		yield 'dealer' => [UserRole::DEALER, ['Nastavenia']];
		yield 'ceo' => [UserRole::CEO, []];
		yield 'admin' => [UserRole::ADMIN, []];
	}

	/**
	 * @param list<string> $hidden
	 */
	#[DataProvider('provideHiddenItems')]
	public function testMenuShowsExactlyTheModulesTheRoleMayOpen(UserRole $role, array $hidden): void
	{
		$this->loginAs($role);

		$links = $this->readMenu($this->renderPresenter('Admin:Home'));

		$expected = array_values(array_diff(array_keys(self::Destinations), $hidden));
		self::assertEqualsCanonicalizing([self::HomeLink, ...$expected], array_keys($links));
	}

	public function testMenuItemsLeadToTheirPresentersInTheOrderOfPositions(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$linkGenerator = $this->getContainer()->getByType(LinkGenerator::class);

		$links = $this->readMenu($this->renderPresenter('Admin:Home'));

		foreach (self::Destinations as $name => $destination) {
			self::assertSame(
				parse_url($linkGenerator->link($destination), PHP_URL_PATH),
				parse_url($links[$name], PHP_URL_PATH),
				$name,
			);
		}

		// Positions 2, 3, 4 (three rows), 5, 6, 7, 8. Rows of the same position have no defined order.
		$names = array_keys($links);
		self::assertSame([self::HomeLink, 'Rodiny', 'Opatrovateľky'], array_slice($names, 0, 3));
		self::assertEqualsCanonicalizing(['Partneri', 'Agentúry', 'Pracovníci'], array_slice($names, 3, 3));
		self::assertSame(['Projekty', 'Evidencia', 'Nastavenia', 'Todo list'], array_slice($names, 6));
	}

	public function testRowWithUrlUnknownToTheCodeIsNotShownAndDoesNotBreakThePage(): void
	{
		TestDatabase::insert(PageTableMap::TABLE_NAME, [
			PageTableMap::COL_ID => 100,
			PageTableMap::COL_NAME => 'Neznámy modul',
			PageTableMap::COL_URL => 'neznamy-modul',
			PageTableMap::COL_PARENT => 0,
			PageTableMap::COL_PERMISSION => 2,
			PageTableMap::COL_ACTIVE => 1,
			PageTableMap::COL_IN_MENU => 1,
			PageTableMap::COL_POSITION => 1,
		]);
		$this->loginAs(UserRole::ADMIN);

		$source = $this->renderPresenter('Admin:Home');

		self::assertStringNotContainsString('Neznámy modul', $source);
		self::assertCount(count(self::Destinations) + 1, $this->readMenu($source));
	}

	public function testPermissionColumnOfTheRowGrantsNothing(): void
	{
		// The row says "from DEALER_JUNIOR", the code says partners start at DEALER.
		$this->getDatabase()->table(PageTableMap::TABLE_NAME)
			->where(PageTableMap::COL_URL, MenuPage::PARTNER->value)
			->update([PageTableMap::COL_PERMISSION => UserRole::DEALER_JUNIOR->getPermissionId()]);
		$this->loginAs(UserRole::DEALER_JUNIOR);

		$links = $this->readMenu($this->renderPresenter('Admin:Home'));

		self::assertArrayNotHasKey('Partneri', $links);
	}

	public function testEveryMenuRowOfTheSeedIsKnownToTheCode(): void
	{
		$names = [];
		foreach ($this->getContainer()->getByType(PageRepository::class)->findMenuItems() as $row) {
			$url = (string) $row->{PageTableMap::COL_URL};
			self::assertNotNull(MenuPage::tryFrom($url), 'Menu row "' . $url . '" has no MenuPage case and would disappear from the menu.');
			$names[] = (string) $row->{PageTableMap::COL_NAME};
		}

		self::assertEqualsCanonicalizing(array_keys(self::Destinations), $names);
	}

	protected function tearDown(): void
	{
		$this->logout();

		parent::tearDown();
	}

	/**
	 * @return array<string, string> name of the menu link => href, in display order
	 */
	private function readMenu(string $source): array
	{
		$html = HTMLDocument::createFromString($source, LIBXML_NOERROR, 'UTF-8');

		$links = [];
		foreach ($html->querySelectorAll('#navbarNav ul.navbar-nav a.nav-link') as $link) {
			self::assertStringNotContainsString('disabled', (string) $link->getAttribute('class'));
			$links[trim((string) $link->textContent)] = (string) $link->getAttribute('href');
		}

		return $links;
	}
}
