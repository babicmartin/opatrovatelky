<?php declare(strict_types=1);

namespace App\UI\Admin\Agency;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Agency\AgencyUpdate\AgencyUpdateForm;
use App\Model\Repository\AgencyRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\AgencyTableMap;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Agency\AgencyDocuments\AgencyDocumentsControl;
use App\UI\Admin\Control\Agency\AgencyDocuments\AgencyDocumentsControlFactory;
use App\UI\Admin\Control\Agency\AgencyList\AgencyListPresenterTrait;
use App\UI\Admin\Form\Agency\AgencyUpdate\AgencyUpdateFormFactory;
use Nette\Application\UI\Form;

class AgencyPresenter extends AdminPresenter
{
	use AgencyListPresenterTrait;

	private int $agencyId = 0;

	/** @var array<string, mixed>|null */
	private ?array $agency = null;

	public function __construct(
		private readonly AgencyRepository $agencyRepository,
		private readonly AgencyUpdateFormFactory $agencyUpdateFormFactory,
		private readonly AgencyDocumentsControlFactory $agencyDocumentsControlFactory,
		private readonly ChangeAuditLogger $changeAuditLogger,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::AGENCY->value;
	}

	public function actionDefault(?int $status = null, ?int $country = null): void
	{
		$this->template->status = $status;
		$this->template->country = $country;
		$this->template->countries = $this->agencyRepository->findCountryOptions();
		$this->template->statuses = $this->agencyRepository->findStatusOptions();
		$this->template->canManageAgency = $this->getUser()->isAllowed(Resource::AGENCY->value);
	}

	public function actionUpdate(int $id): void
	{
		if (!$this->getUser()->isAllowed(Resource::AGENCY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->agencyId = $id;
		$this->agency = $this->agencyRepository->findUpdateRow($id);
		if ($this->agency === null) {
			$this->error('Agentúra neexistuje.', 404);
		}

		$this->template->id = $id;
		$this->template->agency = $this->agency;
		$this->template->canManageAgency = $this->getUser()->isAllowed(Resource::AGENCY->value);
		$this->template->canOpenBabysitter = $this->getUser()->isAllowed(Resource::BABYSITTER->value);
		$this->template->canOpenFamily = $this->getUser()->isAllowed(Resource::FAMILY->value);
		$this->template->babysitterRows = $this->agencyRepository->findBabysittersForAgency($id);
		$this->template->familyRows = $this->agencyRepository->findFamiliesForAgency($id);
	}

	public function handleCreate(): void
	{
		if (!$this->getUser()->isAllowed(Resource::AGENCY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->agencyRepository->createEmptyAgency();
		$this->changeAuditLogger->logCreated('agency.update', AgencyTableMap::TABLE_NAME, $id, 'Agentúra');
		$this->redirect('update', $id);
	}

	protected function createComponentAgencyUpdateForm(): Form
	{
		return $this->agencyUpdateFormFactory->create(
			$this->getAgency(),
			$this->agencyRepository->findCountrySelectOptions(),
			$this->agencyRepository->findStatusSelectOptions(),
			$this->agencyUpdateFormSucceeded(...),
		);
	}

	protected function createComponentAgencyDocuments(): AgencyDocumentsControl
	{
		return $this->agencyDocumentsControlFactory->create()
			->setContext($this->agencyId);
	}

	private function agencyUpdateFormSucceeded(AgencyUpdateForm $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::AGENCY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->tryHandleAutosavePartialRequest();
		$this->agencyRepository->updateFromForm($form);
		$this->finishAutosave();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getAgency(): array
	{
		if ($this->agency === null) {
			$this->agency = $this->agencyRepository->findUpdateRow($this->agencyId);
			if ($this->agency === null) {
				$this->error('Agentúra neexistuje.', 404);
			}
		}

		return $this->agency;
	}

	private function finishAutosave(): void
	{
		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->redirect('this');
	}
}
