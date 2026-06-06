<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\UserRole\UserRole;
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

	#[DataProvider('adminPresenters')]
	public function testDealerRoleIsForbidden(string $presenter): void
	{
		$this->loginAs(UserRole::DEALER);

		try {
			$this->runPresenter($presenter);
			self::fail($presenter . ' must reject the dealer role.');
		} catch (BadRequestException $exception) {
			self::assertSame(403, $exception->getCode());
		}
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
