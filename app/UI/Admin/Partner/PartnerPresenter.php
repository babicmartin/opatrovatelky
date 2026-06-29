<?php declare(strict_types=1);

namespace App\UI\Admin\Partner;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Partner\PartnerUpdate\PartnerUpdateForm;
use App\Model\Repository\PartnerRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\PartnerTableMap;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Partner\PartnerDocuments\PartnerDocumentsControl;
use App\UI\Admin\Control\Partner\PartnerDocuments\PartnerDocumentsControlFactory;
use App\UI\Admin\Control\Partner\PartnerList\PartnerListPresenterTrait;
use App\UI\Admin\Form\Partner\PartnerUpdate\PartnerUpdateFormFactory;
use Nette\Application\UI\Form;

class PartnerPresenter extends AdminPresenter
{
	use PartnerListPresenterTrait;

	private int $partnerId = 0;

	/** @var array<string, mixed>|null */
	private ?array $partner = null;

	public function __construct(
		private readonly PartnerRepository $partnerRepository,
		private readonly PartnerUpdateFormFactory $partnerUpdateFormFactory,
		private readonly PartnerDocumentsControlFactory $partnerDocumentsControlFactory,
		private readonly ChangeAuditLogger $changeAuditLogger,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::PARTNER->value;
	}

	public function actionDefault(?int $status = null, ?int $country = null): void
	{
		$this->template->status = $status;
		$this->template->country = $country;
		$this->template->countries = $this->partnerRepository->findCountryOptions();
		$this->template->statuses = $this->partnerRepository->findStatusOptions();
		$this->template->canManagePartner = $this->getUser()->isAllowed(Resource::PARTNER->value);
	}

	public function actionUpdate(int $id): void
	{
		if (!$this->getUser()->isAllowed(Resource::PARTNER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->partnerId = $id;
		$this->partner = $this->partnerRepository->findUpdateRow($id);
		if ($this->partner === null) {
			$this->error('Partner neexistuje.', 404);
		}

		$this->template->id = $id;
		$this->template->partner = $this->partner;
		$this->template->canManagePartner = $this->getUser()->isAllowed(Resource::PARTNER->value);
		$this->template->canOpenFamily = $this->getUser()->isAllowed(Resource::FAMILY->value);
		$this->template->familyRows = $this->partnerRepository->findFamiliesForPartner($id);
	}

	public function handleCreate(): void
	{
		$this->redirect('default');
	}

	protected function createComponentPartnerUpdateForm(): Form
	{
		return $this->partnerUpdateFormFactory->create(
			$this->getPartner(),
			$this->partnerRepository->findCountrySelectOptions(),
			$this->partnerRepository->findStatusSelectOptions(),
			$this->partnerUpdateFormSucceeded(...),
		);
	}

	protected function createComponentCreatePartnerForm(): Form
	{
		if (!$this->getUser()->isAllowed(Resource::PARTNER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		return $this->createConfirmedCreateForm(
			'Pridať nového partnera',
			'Naozaj chcete vytvoriť nového partnera?',
			$this->createPartnerFormSucceeded(...),
		);
	}

	protected function createComponentPartnerDocuments(): PartnerDocumentsControl
	{
		return $this->partnerDocumentsControlFactory->create()
			->setContext($this->partnerId);
	}

	private function partnerUpdateFormSucceeded(PartnerUpdateForm $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::PARTNER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->tryHandleAutosavePartialRequest();
		$this->partnerRepository->updateFromForm($form);
		$this->finishAutosave();
	}

	private function createPartnerFormSucceeded(Form $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::PARTNER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->partnerRepository->createEmptyPartner();
		$this->changeAuditLogger->logCreated('partner.update', PartnerTableMap::TABLE_NAME, $id, 'Partner');
		$this->redirect('update', $id);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getPartner(): array
	{
		if ($this->partner === null) {
			$this->partner = $this->partnerRepository->findUpdateRow($this->partnerId);
			if ($this->partner === null) {
				$this->error('Partner neexistuje.', 404);
			}
		}

		return $this->partner;
	}

	private function finishAutosave(): void
	{
		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->redirect('this');
	}
}
