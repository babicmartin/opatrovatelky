<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Form\DTO\Admin\Agency\AgencyUpdate\AgencyUpdateForm;
use App\Model\Form\DTO\Admin\Partner\PartnerUpdate\PartnerUpdateForm;
use App\Model\Form\DTO\Admin\Proposal\ProposalUpdate\ProposalUpdateForm;
use App\Model\Form\DTO\Admin\Todo\TodoUpdate\TodoUpdateForm;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserAccessUpdateForm;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserPasswordUpdateForm;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserProfileUpdateForm;
use App\Model\Table\TodoClientTableMap;
use App\Model\Table\UserTableMap;
use App\UI\Admin\Form\Agency\AgencyUpdate\AgencyUpdateFormFactory;
use App\UI\Admin\Form\Country\CountryUpdate\CountryUpdateFormFactory;
use App\UI\Admin\Form\MissingRegistry\MissingRegistryFormFactory;
use App\UI\Admin\Form\Partner\PartnerUpdate\PartnerUpdateFormFactory;
use App\UI\Admin\Form\Proposal\ProposalUpdate\ProposalUpdateFormFactory;
use App\UI\Admin\Form\Todo\TodoUpdate\TodoUpdateFormFactory;
use App\UI\Admin\Form\UserManagement\UserProfileUpdate\UserProfileUpdateFormFactory;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\SubmitterControl;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class AdminSupportingFormFactoryTest extends DatabaseTestCase
{
	public function testAgencyAndPartnerFormsMapDefaultsAndSubmittedDtos(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$agencyDto = null;
		$agencyForm = $this->getContainer()
			->getByType(AgencyUpdateFormFactory::class)
			->create($this->companyFormData(10, 'Agency default'), [1 => 'Slovensko', 2 => 'Rakúsko'], [1 => 'Aktívny'], function (AgencyUpdateForm $form) use (&$agencyDto): void {
				$agencyDto = $form;
			});
		$partnerDto = null;
		$partnerForm = $this->getContainer()
			->getByType(PartnerUpdateFormFactory::class)
			->create($this->companyFormData(11, 'Partner default'), [1 => 'Slovensko', 2 => 'Rakúsko'], [1 => 'Aktívny'], function (PartnerUpdateForm $form) use (&$partnerDto): void {
				$partnerDto = $form;
			});

		self::assertStringContainsString('agency-update-form', (string) $agencyForm->getElementPrototype()->class);
		self::assertSame('Agency default', $this->controlValue($agencyForm, 'name'));
		self::assertSame('15.01.2026', $this->controlValue($agencyForm, 'dateStart'));
		self::assertStringContainsString('partner-update-form', (string) $partnerForm->getElementPrototype()->class);
		self::assertSame('Partner default', $this->controlValue($partnerForm, 'name'));

		$this->submit($agencyForm, $this->companySubmitValues(10, 'Agency submitted'));
		$this->submit($partnerForm, $this->companySubmitValues(11, 'Partner submitted'));

		self::assertInstanceOf(AgencyUpdateForm::class, $agencyDto);
		self::assertSame(10, $agencyDto->id);
		self::assertSame('Agency submitted', $agencyDto->name);
		self::assertSame(2, $agencyDto->state);
		self::assertSame('2026-02-20', $agencyDto->dateStart?->format('Y-m-d'));
		self::assertSame('Submitted notice', $agencyDto->notice);
		self::assertInstanceOf(PartnerUpdateForm::class, $partnerDto);
		self::assertSame(11, $partnerDto->id);
		self::assertSame('Partner submitted', $partnerDto->name);
		self::assertSame('2026-02-20', $partnerDto->dateStart?->format('Y-m-d'));
	}

	public function testTodoProposalMissingAndCountryFormsMapSubmittedValues(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$todoId = TestDatabase::insert(TodoClientTableMap::TABLE_NAME, [
			TodoClientTableMap::COL_TODO_FROM_USER => (int) $this->getContainer()->getByType(User::class)->getId(),
			TodoClientTableMap::COL_TITLE => 'Todo default',
			TodoClientTableMap::COL_STATUS => 2,
		]);
		$todoDto = null;
		$todoForm = $this->getContainer()
			->getByType(TodoUpdateFormFactory::class)
			->create([
				'id' => $todoId,
				'familyId' => 1,
				'babysitterId' => 2,
				'todoFromUser' => 3,
				'todoToUser1' => 4,
				'todoToUser2' => 0,
				'todoCreated' => new \DateTimeImmutable('2026-06-01'),
				'todoDeadline' => new \DateTimeImmutable('2026-06-10'),
				'status' => 2,
				'title' => 'Todo default',
				'description' => 'Description default',
				'answer' => 'Answer default',
			], [0 => '---', 1 => 'Family'], [0 => '---', 2 => 'Babysitter'], [0 => '---', 3 => 'From', 4 => 'To'], [2 => 'Status'], function (TodoUpdateForm $form) use (&$todoDto): void {
				$todoDto = $form;
			});
		$proposalDto = null;
		$proposalForm = $this->getContainer()
			->getByType(ProposalUpdateFormFactory::class)
			->create([
				'id' => 20,
				'status' => 1,
				'babysitterId' => 2,
				'dateStartingWork' => new \DateTimeImmutable('2026-07-01'),
				'dateProposalSended' => new \DateTimeImmutable('2026-07-15'),
				'notice' => 'Proposal default',
			], [1 => 'Nový'], [2 => 'Anna'], function (ProposalUpdateForm $form) use (&$proposalDto): void {
				$proposalDto = $form;
			});
		$missingPayload = null;
		$missingForm = $this->getContainer()
			->getByType(MissingRegistryFormFactory::class)
			->create([
				'id' => 30,
				'userId' => 3,
				'dateFrom' => new \DateTimeImmutable('2026-08-01'),
				'dateTo' => new \DateTimeImmutable('2026-08-03'),
				'typePn' => true,
				'typeOcr' => false,
				'typeLekar' => true,
				'typeSviatok' => false,
				'typeZastup' => true,
				'typeSluzba' => false,
				'typeDovolenka' => false,
				'notice' => 'Missing default',
			], [3 => 'User'], function (int $id, array $values) use (&$missingPayload): void {
				$missingPayload = ['id' => $id, 'values' => $values];
			});
		$countryPayload = null;
		$countryForm = $this->getContainer()
			->getByType(CountryUpdateFormFactory::class)
			->create([
				'id' => 40,
				'name' => 'Country default',
				'german' => 'German default',
			], function (int $id, array $values) use (&$countryPayload): void {
				$countryPayload = ['id' => $id, 'values' => $values];
			});

		self::assertSame('Todo default', $this->controlValue($todoForm, 'title'));
		self::assertSame('01.07.2026', $this->controlValue($proposalForm, 'dateStartingWork'));
		self::assertSame(true, $this->controlValue($missingForm, 'typePn'));
		self::assertSame('Country default', $this->controlValue($countryForm, 'name'));

		$this->submit($todoForm, [
			'id' => $todoId,
			'familyId' => 1,
			'babysitterId' => 2,
			'todoFromUser' => 3,
			'todoToUser1' => 4,
			'todoToUser2' => 0,
			'todoCreated' => '11.06.2026',
			'todoDeadline' => '12.06.2026',
			'status' => 2,
			'title' => 'Todo submitted',
			'description' => 'Description submitted',
			'answer' => 'Answer submitted',
		]);
		$this->submit($proposalForm, [
			'id' => 20,
			'status' => 1,
			'babysitterId' => 2,
			'dateStartingWork' => '21.07.2026',
			'dateProposalSended' => '22.07.2026',
			'notice' => 'Proposal submitted',
		]);
		$this->submit($missingForm, [
			'id' => 30,
			'userId' => 3,
			'dateFrom' => '05.08.2026',
			'dateTo' => '06.08.2026',
			'typePn' => false,
			'typeOcr' => true,
			'typeLekar' => false,
			'typeSviatok' => true,
			'typeZastup' => false,
			'typeSluzba' => true,
			'typeDovolenka' => true,
			'notice' => 'Missing submitted',
		]);
		$this->submit($countryForm, [
			'id' => 40,
			'name' => 'Country submitted',
			'german' => 'German submitted',
		]);

		self::assertInstanceOf(TodoUpdateForm::class, $todoDto);
		self::assertSame('Todo submitted', $todoDto->title);
		self::assertSame('2026-06-11', $todoDto->todoCreated?->format('Y-m-d'));
		self::assertInstanceOf(ProposalUpdateForm::class, $proposalDto);
		self::assertSame('Proposal submitted', $proposalDto->notice);
		self::assertSame('2026-07-21', $proposalDto->dateStartingWork?->format('Y-m-d'));
		self::assertIsArray($missingPayload);
		self::assertSame(30, $missingPayload['id']);
		self::assertSame(true, $missingPayload['values']['typeOcr']);
		self::assertSame('Missing submitted', $missingPayload['values']['notice']);
		self::assertIsArray($countryPayload);
		self::assertSame(40, $countryPayload['id']);
		self::assertSame('Country submitted', $countryPayload['values']['name']);
	}

	public function testUserFormsMapDtosAndValidatePasswordAndUploadForms(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$profileDto = null;
		$accessDto = null;
		$passwordDto = null;
		$imageSubmitted = false;
		$factory = $this->getContainer()->getByType(UserProfileUpdateFormFactory::class);
		$profileForm = $factory->createProfileForm([
			'id' => 50,
			'name' => 'Profile',
			'secondName' => 'Default',
			'acronym' => 'PD',
			'email' => 'profile.default@example.test',
			'color' => '#123456',
		], function (UserProfileUpdateForm $form) use (&$profileDto): void {
			$profileDto = $form;
		});
		$accessForm = $factory->createAccessForm([
			'id' => 50,
			'permission' => 5,
			'active' => 1,
		], [5 => 'CEO'], [1 => 'Aktívny', 2 => 'Neaktívny'], function (UserAccessUpdateForm $form) use (&$accessDto): void {
			$accessDto = $form;
		});
		$passwordForm = $factory->createPasswordForm(function (UserPasswordUpdateForm $form) use (&$passwordDto): void {
			$passwordDto = $form;
		});
		$imageForm = $factory->createImageForm(function () use (&$imageSubmitted): void {
			$imageSubmitted = true;
		});

		self::assertSame('Profile', $this->controlValue($profileForm, 'name'));
		self::assertSame(5, $this->controlValue($accessForm, 'permission'));

		$this->submit($profileForm, [
			'id' => 50,
			'name' => 'Profile submitted',
			'secondName' => 'User',
			'acronym' => 'PS',
			'email' => 'profile.submitted@example.test',
			'color' => '#abcdef',
		]);
		$this->submit($accessForm, [
			'id' => 50,
			'permission' => 5,
			'active' => 2,
		]);
		$this->submit($passwordForm, [
			'password' => 'weak',
			'passwordRepeat' => 'weak',
		]);
		$this->submit($imageForm, []);

		self::assertInstanceOf(UserProfileUpdateForm::class, $profileDto);
		self::assertSame('Profile submitted', $profileDto->getName());
		self::assertSame('profile.submitted@example.test', $profileDto->getEmail());
		self::assertInstanceOf(UserAccessUpdateForm::class, $accessDto);
		self::assertSame(5, $accessDto->getPermission());
		self::assertSame(2, $accessDto->getActive());
		self::assertNull($passwordDto);
		self::assertNotEmpty($passwordForm->getErrors());
		self::assertFalse($imageSubmitted);
		self::assertNotEmpty($imageForm->getErrors());

		$validPasswordForm = $factory->createPasswordForm(function (UserPasswordUpdateForm $form) use (&$passwordDto): void {
			$passwordDto = $form;
		});
		$this->submit($validPasswordForm, [
			'password' => 'Strong123!',
			'passwordRepeat' => 'Strong123!',
		]);

		self::assertInstanceOf(UserPasswordUpdateForm::class, $passwordDto);
		self::assertSame('Strong123!', $passwordDto->getPassword());
	}

	public function testAclProtectedFactoriesDenyUnauthorizedUsers(): void
	{
		$this->loginAs(UserRole::DEALER);

		$this->assertForbidden(function (): void {
			$this->getContainer()
				->getByType(AgencyUpdateFormFactory::class)
				->create($this->companyFormData(60, 'Denied agency'), [1 => 'Slovensko'], [1 => 'Aktívny'], static function (): void {
				});
		});
		$this->assertForbidden(function (): void {
			$this->getContainer()
				->getByType(PartnerUpdateFormFactory::class)
				->create($this->companyFormData(61, 'Denied partner'), [1 => 'Slovensko'], [1 => 'Aktívny'], static function (): void {
				});
		});
		$this->assertForbidden(function (): void {
			$this->getContainer()
				->getByType(TodoUpdateFormFactory::class)
				->create([
					'id' => 62,
					'familyId' => 0,
					'babysitterId' => 0,
					'todoFromUser' => 0,
					'todoToUser1' => 0,
					'todoToUser2' => 0,
					'todoCreated' => null,
					'todoDeadline' => null,
					'status' => 0,
					'title' => '',
					'description' => '',
					'answer' => '',
				], [0 => '---'], [0 => '---'], [0 => '---'], [0 => '---'], static function (): void {
				});
		});
		$this->assertForbidden(function (): void {
			$this->getContainer()
				->getByType(CountryUpdateFormFactory::class)
				->create(['id' => 63, 'name' => 'Denied country', 'german' => 'Denied'], static function (): void {
				});
		});
		$this->assertForbidden(function (): void {
			$this->getContainer()
				->getByType(UserProfileUpdateFormFactory::class)
				->createAccessForm(['id' => 64, 'permission' => 5, 'active' => 1], [5 => 'CEO'], [1 => 'Aktívny'], static function (): void {
				});
		});
	}

	public function testAccessFormAddsErrorWhenPermissionIsLostBeforeSubmit(): void
	{
		$this->loginAs(UserRole::ADMIN);
		$called = false;
		$form = $this->getContainer()
			->getByType(UserProfileUpdateFormFactory::class)
			->createAccessForm([
				'id' => 70,
				'permission' => 5,
				'active' => 1,
			], [5 => 'CEO'], [1 => 'Aktívny'], function () use (&$called): void {
				$called = true;
			});

		$this->loginAs(UserRole::DEALER);
		$this->submit($form, [
			'id' => 70,
			'permission' => 5,
			'active' => 1,
		]);

		self::assertFalse($called);
		self::assertSame(['Prístup zamietnutý.'], $form->getOwnErrors());
	}

	protected function tearDown(): void
	{
		$this->getContainer()->getByType(User::class)->logout(true);

		parent::tearDown();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function companyFormData(int $id, string $name): array
	{
		return [
			'id' => $id,
			'name' => $name,
			'street' => 'Street',
			'streetNumber' => '1',
			'psc' => '81101',
			'city' => 'City',
			'state' => 1,
			'dateStart' => new \DateTimeImmutable('2026-01-15'),
			'personSurname' => 'Surname',
			'personName' => 'Name',
			'ico' => '12345678',
			'icDph' => 'SK12345678',
			'web' => 'example.test',
			'phone' => '+421900111222',
			'email' => 'company@example.test',
			'status' => 1,
			'notice' => 'Default notice',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function companySubmitValues(int $id, string $name): array
	{
		return [
			'id' => $id,
			'name' => $name,
			'street' => 'Submitted street',
			'streetNumber' => '2',
			'psc' => '90001',
			'city' => 'Submitted city',
			'state' => 2,
			'dateStart' => '20.02.2026',
			'personSurname' => 'Submitted surname',
			'personName' => 'Submitted name',
			'ico' => '87654321',
			'icDph' => 'AT87654321',
			'web' => 'submitted.example.test',
			'phone' => '+431234567',
			'email' => 'submitted@example.test',
			'status' => 1,
			'notice' => 'Submitted notice',
		];
	}

	private function loginAs(UserRole $role): int
	{
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => strtolower($role->value) . '.forms.' . uniqid('', true) . '@example.test',
			UserTableMap::COL_PERMISSION => $role->getPermissionId(),
		]);
		$this->getContainer()->getByType(User::class)->login(new SimpleIdentity($userId, [$role->value], [
			'email' => strtolower($role->value) . '.forms@example.test',
		]));

		return $userId;
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function submit(Form $form, array $values): void
	{
		$protector = $form->getComponent(Form::ProtectorId, false);
		if ($protector !== null) {
			$form->removeComponent($protector);
		}

		$form->setValues($values, true);
		$form->validate();
		if ($form->hasErrors()) {
			return;
		}

		$values = ArrayHash::from($this->controlValues($form));
		foreach ($form->onSuccess as $handler) {
			$handler($form, $values);
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function controlValues(Form $form): array
	{
		$values = [];
		foreach ($form->getControls() as $control) {
			if (!$control instanceof BaseControl || $control instanceof SubmitterControl || $control->isOmitted()) {
				continue;
			}

			$name = $control->getName();
			if (is_string($name)) {
				$values[$name] = $control->getValue();
			}
		}

		return $values;
	}

	private function controlValue(Form $form, string $name): mixed
	{
		$control = $form->getComponent($name);
		self::assertInstanceOf(BaseControl::class, $control);

		return $control->getValue();
	}

	/**
	 * @param callable(): void $callback
	 */
	private function assertForbidden(callable $callback): void
	{
		try {
			$callback();
			self::fail('Factory must deny unauthorized access.');
		} catch (ForbiddenRequestException) {
			self::addToAssertionCount(1);
		}
	}
}
