<?php declare(strict_types=1);

namespace App\UI\Admin\Family;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Family\FamilyAddress\FamilyAddressForm;
use App\Model\Form\DTO\Admin\Family\FamilyInfo\FamilyInfoForm;
use App\Model\Form\DTO\Admin\Family\FamilyShortInfo\FamilyShortInfoForm;
use App\Model\Repository\FamilyRepository;
use App\Model\Repository\FamilyProposalRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\TurnusTableMap;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Family\FamilyDocuments\FamilyDocumentsControl;
use App\UI\Admin\Control\Family\FamilyDocuments\FamilyDocumentsControlFactory;
use App\UI\Admin\Control\Family\FamilyList\FamilyListPresenterTrait;
use App\UI\Admin\Control\Filter\AlphabetFilter\AlphabetFilterPresenterTrait;
use App\UI\Admin\Form\Family\FamilyAddress\FamilyAddressFormFactory;
use App\UI\Admin\Form\Family\FamilyInfo\FamilyInfoFormFactory;
use App\UI\Admin\Form\Family\FamilyShortInfo\FamilyShortInfoFormFactory;
use Nette\Application\UI\Form;

class FamilyPresenter extends AdminPresenter
{
	use AlphabetFilterPresenterTrait;
	use FamilyListPresenterTrait;

	private int $familyId = 0;

	private string $activeTab = 'main';

	/** @var array<string, mixed>|null */
	private ?array $family = null;

	public function __construct(
		private readonly FamilyRepository $familyRepository,
		private readonly FamilyProposalRepository $familyProposalRepository,
		private readonly FamilyInfoFormFactory $familyInfoFormFactory,
		private readonly FamilyAddressFormFactory $familyAddressFormFactory,
		private readonly FamilyShortInfoFormFactory $familyShortInfoFormFactory,
		private readonly FamilyDocumentsControlFactory $familyDocumentsControlFactory,
		private readonly ChangeAuditLogger $changeAuditLogger,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::FAMILY->value;
	}

	public function actionDefault(
		?int $page = null,
		?int $status = null,
		?int $country = null,
		?int $partner = null,
		?string $city = null,
		?int $user = null,
	): void
	{
		$firstLetterRaw = $this->getHttpRequest()->getQuery('first-letter');
		$firstLetter = is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null;
		$canManageFamilies = $this->getUser()->isLoggedIn()
			&& $this->getUser()->isAllowed(Resource::FAMILY_MANAGEMENT->value);

		$this->template->page = $page;
		$this->template->status = $status;
		$this->template->country = $country;
		$this->template->partner = $partner;
		$this->template->city = $city;
		$this->template->managerUser = $user;
		$this->template->firstLetter = $firstLetter;
		$this->template->canManageFamilies = $canManageFamilies;
		$this->template->countries = $this->familyRepository->findCountryOptions();
		$this->template->statuses = $this->familyRepository->findStatusOptions();
		$this->template->partners = $this->familyRepository->findPartnerOptions();
		$this->template->cities = $this->familyRepository->findCityOptions();
		$this->template->managerCounts = $canManageFamilies ? $this->familyRepository->findManagerCounts() : [];
	}

	public function actionUpdate(
		int $id,
		?string $tab = null,
		?int $address = null,
		?int $proposal = null,
		?int $contract = null,
		?int $order = null,
	): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->familyId = $id;
		$this->familyRepository->generateClientNumberIfMissing($id);
		$this->family = $this->familyRepository->findUpdateRow($id);
		if ($this->family === null) {
			$this->error('Rodina neexistuje.', 404);
		}

		$this->activeTab = $this->normalizeTab($tab, $address, $proposal, $contract, $order);

		$this->template->id = $id;
		$this->template->family = $this->family;
		$this->template->activeTab = $this->activeTab;
		$this->template->pageTitleText = (int) $this->family['type'] === 1 ? 'Rodina' : 'Projekt';
		$this->template->canManageFamily = $this->getUser()->isAllowed(Resource::FAMILY->value);
		$this->template->canOpenProposal = $this->getUser()->isAllowed(Resource::PROPOSAL->value);
		$this->template->canOpenBabysitter = $this->getUser()->isAllowed(Resource::BABYSITTER->value);
		$this->template->canOpenTurnus = $this->getUser()->isAllowed(Resource::TURNUS->value);
		$this->template->canDeleteFamily = $this->familyRepository->canDeleteFamily($id);
		$turnusRows = $this->activeTab === 'main' ? $this->familyRepository->findTurnusRowsForFamily($id) : [];
		$this->template->turnusRowsWithoutDate = array_values(array_filter(
			$turnusRows,
			static fn (array $turnus): bool => $turnus['dateFrom'] === null,
		));
		$this->template->turnusRowsWithDate = array_values(array_filter(
			$turnusRows,
			static fn (array $turnus): bool => $turnus['dateFrom'] !== null,
		));
		$this->template->proposalRows = $this->activeTab === 'proposals' ? $this->familyProposalRepository->findRowsByFamilyId($id) : [];
	}

	public function handleCreate(): void
	{
		$this->redirect('default');
	}

	public function handleCreateTurnus(int $familyId): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->familyRepository->createTurnusForFamily($familyId, (int) $this->getUser()->getId());
		$this->changeAuditLogger->logCreated('turnus.update', TurnusTableMap::TABLE_NAME, $id, 'Turnus', [
			'created_from' => 'family',
			'family_id' => $familyId,
		]);
		$this->redirect(':Admin:Turnus:update', $id);
	}

	public function handleCreateProposal(int $familyId): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->familyProposalRepository->createForFamily($familyId, (int) $this->getUser()->getId());
		$this->changeAuditLogger->logCreated('proposal.update', FamilyProposalTableMap::TABLE_NAME, $id, 'Návrh', [
			'created_from' => 'family',
			'family_id' => $familyId,
		]);
		$this->redirect(':Admin:Proposal:update', $id);
	}

	public function handleDelete(int $familyId): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->familyRepository->softDeleteIfNoTurnus($familyId);
		$this->changeAuditLogger->logDeleted('family.shortInfo', FamilyTableMap::TABLE_NAME, $familyId, 'Rodina/projekt');
		$this->redirect('default');
	}

	protected function createComponentFamilyShortInfoForm(): Form
	{
		$family = $this->getFamily();

		return $this->familyShortInfoFormFactory->create(
			$family,
			$this->familyRepository->findCountrySelectOptions(),
			$this->familyShortInfoFormSucceeded(...),
		);
	}

	protected function createComponentCreateFamilyForm(): Form
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		return $this->createConfirmedCreateForm(
			'Pridať novú rodinu',
			'Naozaj chcete vytvoriť novú rodinu?',
			$this->createFamilyFormSucceeded(...),
		);
	}

	protected function createComponentFamilyInfoForm(): Form
	{
		$family = $this->getFamily();
		$userOptions = $this->familyRepository->findUserSelectOptions([
			(int) $family['acquiredByUserId'],
			(int) $family['userId'],
		]);

		return $this->familyInfoFormFactory->create(
			$family,
			$this->familyRepository->findFamilyTypeOptions(),
			$this->familyRepository->findPartnerSelectOptions(),
			$userOptions,
			$this->familyRepository->findStatusSelectOptions(),
			$this->familyRepository->findDocumentStatusSelectOptions(),
			$this->familyRepository->findWorkStatusStaffOptions(),
			$this->familyInfoFormSucceeded(...),
		);
	}

	protected function createComponentFamilyAddressForm(): Form
	{
		return $this->familyAddressFormFactory->create(
			$this->getFamily(),
			$this->familyAddressFormSucceeded(...),
		);
	}

	protected function createComponentFamilyContractsDocuments(): FamilyDocumentsControl
	{
		return $this->familyDocumentsControlFactory->create()
			->setContext($this->familyId, 'families-orders', 'Zmluvy');
	}

	protected function createComponentFamilyOrdersDocuments(): FamilyDocumentsControl
	{
		return $this->familyDocumentsControlFactory->create()
			->setContext($this->familyId, 'families-contracts', 'Objednávky');
	}

	private function familyInfoFormSucceeded(FamilyInfoForm $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->tryHandleAutosavePartialRequest();
		$this->familyRepository->updateInfoFromForm($form);
		$this->finishAutosave();
	}

	private function familyAddressFormSucceeded(FamilyAddressForm $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->tryHandleAutosavePartialRequest();
		$this->familyRepository->updateAddressFromForm($form);
		$this->finishAutosave();
	}

	private function familyShortInfoFormSucceeded(FamilyShortInfoForm $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->tryHandleAutosavePartialRequest();
		$this->familyRepository->updateShortInfoFromForm($form);
		$this->finishAutosave();
	}

	private function createFamilyFormSucceeded(Form $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::FAMILY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->familyRepository->createEmptyFamily();
		$this->changeAuditLogger->logCreated('family.shortInfo', FamilyTableMap::TABLE_NAME, $id, 'Rodina', [
			'created_as' => 'family',
		]);
		$this->redirect('update', $id);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getFamily(): array
	{
		if ($this->family === null) {
			$this->family = $this->familyRepository->findUpdateRow($this->familyId);
			if ($this->family === null) {
				$this->error('Rodina neexistuje.', 404);
			}
		}

		return $this->family;
	}

	private function finishAutosave(): void
	{
		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->redirect('this');
	}

	private function normalizeTab(?string $tab, ?int $address, ?int $proposal, ?int $contract, ?int $order): string
	{
		if ($tab === null) {
			if ($address === 1) {
				return 'info';
			}
			if ($proposal === 1) {
				return 'proposals';
			}
			if ($contract === 1) {
				return 'contracts';
			}
			if ($order === 1) {
				return 'orders';
			}

			return 'main';
		}

		return in_array($tab, ['main', 'info', 'proposals', 'contracts', 'orders'], true) ? $tab : 'main';
	}
}
