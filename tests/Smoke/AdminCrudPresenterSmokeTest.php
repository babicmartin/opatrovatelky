<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Latte\LatteDefaultVariables;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\TodoClientTableMap;
use App\Model\Table\TurnusTableMap;
use Nette\Application\BadRequestException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

final class AdminCrudPresenterSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function adminPresenters(): iterable
	{
		yield 'Family' => ['Admin:Family'];
		yield 'Partner' => ['Admin:Partner'];
		yield 'Agency' => ['Admin:Agency'];
		yield 'Turnus' => ['Admin:Turnus'];
		yield 'Todo' => ['Admin:Todo'];
		yield 'Country' => ['Admin:Country'];
		yield 'Proposal' => ['Admin:Proposal'];
		yield 'UserManagement' => ['Admin:UserManagement'];
		yield 'Translation' => ['Admin:Translation'];
		yield 'MissingRegistry' => ['Admin:MissingRegistry'];
	}

	#[DataProvider('adminPresenters')]
	public function testDefaultActionRendersForAdmin(string $presenter): void
	{
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter($presenter);

		self::assertNotSame('', trim($html));
		self::assertStringContainsString('</html>', $html);
	}

	/**
	 * Presenter => lowest role that may open it, as set in AuthorizatorFactory.
	 *
	 * @return iterable<string, array{string, UserRole}>
	 */
	public static function lowestRoles(): iterable
	{
		yield 'Home' => ['Admin:Home', UserRole::DEALER_JUNIOR];
		yield 'Babysitter' => ['Admin:Babysitter', UserRole::DEALER_JUNIOR];
		yield 'Family' => ['Admin:Family', UserRole::DEALER_JUNIOR];
		yield 'Worker' => ['Admin:Worker', UserRole::DEALER_JUNIOR];
		yield 'Project' => ['Admin:Project', UserRole::DEALER_JUNIOR];
		yield 'Turnus' => ['Admin:Turnus', UserRole::DEALER_JUNIOR];
		yield 'Stats' => ['Admin:Stats', UserRole::DEALER_JUNIOR];
		yield 'Todo' => ['Admin:Todo', UserRole::DEALER_JUNIOR];
		yield 'Proposal' => ['Admin:Proposal', UserRole::DEALER_JUNIOR];
		yield 'MissingRegistry' => ['Admin:MissingRegistry', UserRole::DEALER_JUNIOR];
		yield 'Partner' => ['Admin:Partner', UserRole::DEALER];
		yield 'Agency' => ['Admin:Agency', UserRole::DEALER];
		yield 'Settings' => ['Admin:Settings', UserRole::CEO];
		yield 'Country' => ['Admin:Country', UserRole::CEO];
		yield 'UserManagement' => ['Admin:UserManagement', UserRole::CEO];
		yield 'Translation' => ['Admin:Translation', UserRole::CEO];
		yield 'ChangeLog' => ['Admin:ChangeLog', UserRole::CEO];
		yield 'LoginLog' => ['Admin:LoginLog', UserRole::CEO];
		// Admin:PartnerFamily (CEO) needs a partner id; PartnerFamilyPresenterSmokeTest covers both sides.
	}

	#[DataProvider('lowestRoles')]
	public function testLowestAllowedRoleRendersDefaultAction(string $presenter, UserRole $lowestRole): void
	{
		$this->loginAs($lowestRole);

		self::assertStringContainsString('</html>', $this->renderPresenter($presenter));
	}

	/**
	 * Presenter => highest role that may not open it. Pages open to DEALER_JUNIOR have no such role:
	 * every signed-in user may open them and only a guest is turned away.
	 *
	 * @return iterable<string, array{string, UserRole}>
	 */
	public static function rolesBelowTheLowestAllowed(): iterable
	{
		foreach (self::lowestRoles() as $name => [$presenter, $lowestRole]) {
			$roleBelow = match ($lowestRole) {
				UserRole::DEALER_JUNIOR => null,
				UserRole::DEALER => UserRole::DEALER_JUNIOR,
				UserRole::CEO => UserRole::DEALER,
				UserRole::ADMIN => UserRole::CEO,
			};
			if ($roleBelow !== null) {
				yield $name => [$presenter, $roleBelow];
			}
		}
	}

	#[DataProvider('rolesBelowTheLowestAllowed')]
	public function testRoleBelowTheLowestAllowedIsForbidden(string $presenter, UserRole $roleBelow): void
	{
		$this->loginAs($roleBelow);

		try {
			$this->runPresenter($presenter);
			self::fail($presenter . ' must reject the role ' . $roleBelow->value . '.');
		} catch (BadRequestException $exception) {
			self::assertSame(403, $exception->getCode());
		}
	}

	public function testMissingRegistryLoadsAutosaveOnceWithCurrentVersion(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$html = $this->renderPresenter('Admin:MissingRegistry');
		preg_match_all('~<script\b[^>]*\bsrc="([^"]*/js/autosaveForm\.js(?:\?[^"]*)?)"~', $html, $matches);
		$version = $this->getContainer()->getByType(LatteDefaultVariables::class)->getJsVersion();
		self::assertSame(['/js/autosaveForm.js?v=' . $version], $matches[1]);
	}

	public function testFamilyUpdateRendersForAdmin(): void
	{
		$id = TestDatabase::createFamily();

		$this->assertUpdateRenders('Admin:Family', $id);
	}

	public function testPartnerUpdateRendersForAdmin(): void
	{
		$id = TestDatabase::createPartner();

		$this->assertUpdateRenders('Admin:Partner', $id);
	}

	public function testAgencyUpdateRendersForAdmin(): void
	{
		$id = TestDatabase::createAgency();

		$this->assertUpdateRenders('Admin:Agency', $id);
	}

	public function testTurnusUpdateRendersForAdmin(): void
	{
		$familyId = TestDatabase::createFamily();
		$babysitterId = TestDatabase::createBabysitter();
		$id = TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
		]);

		$this->assertUpdateRenders('Admin:Turnus', $id);
	}

	public function testTodoUpdateRendersForAdmin(): void
	{
		$id = TestDatabase::insert(TodoClientTableMap::TABLE_NAME, [
			TodoClientTableMap::COL_TITLE => 'Smoke todo',
			TodoClientTableMap::COL_STATUS => 2,
		]);

		$this->assertUpdateRenders('Admin:Todo', $id);
	}

	public function testCountryUpdateRendersForAdmin(): void
	{
		$id = TestDatabase::createCountry();

		$this->assertUpdateRenders('Admin:Country', $id);
	}

	public function testProposalUpdateRendersForAdmin(): void
	{
		$familyId = TestDatabase::createFamily();
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-PRSMK',
		]);
		$id = TestDatabase::insert(FamilyProposalTableMap::TABLE_NAME, [
			FamilyProposalTableMap::COL_FAMILY_ID => $familyId,
			FamilyProposalTableMap::COL_BABYSITTER_ID => $babysitterId,
			FamilyProposalTableMap::COL_STATUS => 1,
			FamilyProposalTableMap::COL_DELETED => 0,
		]);

		$this->assertUpdateRenders('Admin:Proposal', $id);
	}

	public function testUserManagementUpdateRendersForAdmin(): void
	{
		$adminId = $this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter('Admin:UserManagement', ['action' => 'update', 'id' => $adminId]);

		self::assertNotSame('', trim($html));
		self::assertStringContainsString('</html>', $html);
	}

	private function assertUpdateRenders(string $presenter, int $id): void
	{
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter($presenter, ['action' => 'update', 'id' => $id]);

		self::assertNotSame('', trim($html));
		self::assertStringContainsString('</html>', $html);
	}

	protected function tearDown(): void
	{
		$this->logout();

		parent::tearDown();
	}
}
