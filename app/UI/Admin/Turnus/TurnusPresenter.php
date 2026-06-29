<?php declare(strict_types=1);

namespace App\UI\Admin\Turnus;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Turnus\TurnusUpdate\TurnusUpdateForm;
use App\Model\Repository\TurnusRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\TurnusTableMap;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Turnus\TurnusDocuments\TurnusDocumentsControl;
use App\UI\Admin\Control\Turnus\TurnusDocuments\TurnusDocumentsControlFactory;
use App\UI\Admin\Control\Turnus\TurnusList\TurnusListPresenterTrait;
use App\UI\Admin\Form\Turnus\TurnusUpdate\TurnusUpdateFormFactory;
use DateTimeImmutable;
use Nette\Application\UI\Form;

class TurnusPresenter extends AdminPresenter
{
	use TurnusListPresenterTrait;

	private const int FIRST_MONTH_YEAR = 2023;
	private const int CANCELLED_STATUS_ID = 30;
	private const array MONTHS = [
		1 => 'Január',
		2 => 'Február',
		3 => 'Marec',
		4 => 'Apríl',
		5 => 'Máj',
		6 => 'Jún',
		7 => 'Júl',
		8 => 'August',
		9 => 'September',
		10 => 'Október',
		11 => 'November',
		12 => 'December',
	];

	private int $turnusId = 0;

	/** @var array<string, mixed>|null */
	private ?array $turnus = null;

	public function __construct(
		private readonly TurnusRepository $turnusRepository,
		private readonly TurnusUpdateFormFactory $turnusUpdateFormFactory,
		private readonly TurnusDocumentsControlFactory $turnusDocumentsControlFactory,
		private readonly ChangeAuditLogger $changeAuditLogger,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::TURNUS->value;
	}

	public function actionDefault(?int $page = null, ?int $finish = null, ?int $status = null, ?int $country = null, ?int $agency = null, ?int $order = null): void
	{
		$statusId = $status !== null && $status > 0 ? $status : null;
		$countryId = $country !== null && $country > 0 ? $country : null;
		$agencyId = $agency !== null && $agency > 0 ? $agency : null;
		$finish = $finish === 1 ? 1 : 0;
		$order = in_array($order, [1, 2, 3, 4], true) ? $order : 0;

		$this->template->page = $page;
		$this->template->finish = $finish;
		$this->template->status = $statusId;
		$this->template->country = $countryId;
		$this->template->agency = $agencyId;
		$this->template->order = $order;
		$this->template->listMode = $statusId === self::CANCELLED_STATUS_ID ? 'cancelled' : ($finish === 1 ? 'finished' : 'current');
		$this->template->statuses = $this->turnusRepository->findStatusOptions();
		$this->template->countries = $this->turnusRepository->findCountryFilterOptions($finish, $statusId, $order, $agencyId);
		$this->template->agencies = $this->turnusRepository->findAgencyFilterOptions($finish, $statusId, $order, $countryId);
		$this->template->canManageTurnus = $this->getUser()->isAllowed(Resource::TURNUS->value);
	}

	public function actionUpdate(int $id): void
	{
		$this->assertCanManage();

		$this->turnusId = $id;
		$this->turnus = $this->turnusRepository->findUpdateRow($id);
		if ($this->turnus === null) {
			$this->error('Turnus neexistuje.', 404);
		}

		$this->template->id = $id;
		$this->template->turnus = $this->turnus;
		$this->template->showWorkPosition = (int) ($this->turnus['workerType'] ?? 0) === 2;
		$this->template->canManageTurnus = $this->getUser()->isAllowed(Resource::TURNUS->value);
		$this->template->canOpenFamily = $this->getUser()->isAllowed(Resource::FAMILY->value);
		$this->template->canOpenBabysitter = $this->getUser()->isAllowed(Resource::BABYSITTER->value);
	}

	public function handleCreate(): void
	{
		$this->redirect('default');
	}

	public function handleDelete(int $id): void
	{
		$this->assertCanManage();

		$this->turnusRepository->softDelete($id);
		$this->changeAuditLogger->logDeleted('turnus.update', TurnusTableMap::TABLE_NAME, $id, 'Turnus');
		$this->flashMessage('Turnus bol odstránený.', 'success');
		$this->redirect('default');
	}

	public function actionSelectMonth(): void
	{
		$this->template->years = range($this->getCurrentYear(), self::FIRST_MONTH_YEAR);
		$this->template->months = self::MONTHS;
	}

	public function actionMonth(?int $year = null, int $month = 1): void
	{
		$year ??= $this->getCurrentYear();
		$month = max(1, min(12, $month));

		$this->template->year = $year;
		$this->template->month = $month;
		$this->template->monthName = self::MONTHS[$month];
		$this->template->turnuses = $this->turnusRepository->findForMonth($year, $month);
	}

	protected function createComponentTurnusUpdateForm(): Form
	{
		$turnus = $this->getTurnus();

		return $this->turnusUpdateFormFactory->create(
			$turnus,
			$this->turnusRepository->findStatusOptions(),
			$this->turnusRepository->findFamilySelectOptions(),
			$this->turnusRepository->findBabysitterSelectOptions(),
			$this->turnusRepository->findUserSelectOptions(),
			$this->turnusRepository->findAgencySelectOptions(),
			$this->turnusRepository->findPartnerSelectOptions(),
			$this->turnusRepository->findWorkingStatusSelectOptions(),
			$this->turnusRepository->findWorkPositionSelectOptions(),
			$this->turnusRepository->findInvoiceStatusSelectOptions(),
			$this->turnusRepository->findPaymentPeriodSelectOptions(),
			$this->turnusRepository->findComplaintStatusSelectOptions(),
			(int) ($turnus['workerType'] ?? 0) === 2,
			$this->turnusUpdateFormSucceeded(...),
		);
	}

	protected function createComponentCreateTurnusForm(): Form
	{
		$this->assertCanManage();

		return $this->createConfirmedCreateForm(
			'Pridať nový turnus',
			'Naozaj chcete vytvoriť nový turnus?',
			$this->createTurnusFormSucceeded(...),
		);
	}

	protected function createComponentTurnusStatusA1Form(): Form
	{
		$turnus = $this->getTurnus();

		return $this->turnusUpdateFormFactory->createStatusA1(
			$turnus,
			$this->turnusRepository->findStatusA1SelectOptions(),
			$this->turnusStatusA1FormSucceeded(...),
		);
	}

	protected function createComponentTurnusDocuments(): TurnusDocumentsControl
	{
		$this->assertCanManage();

		return $this->turnusDocumentsControlFactory->create()
			->setContext($this->turnusId);
	}

	private function turnusUpdateFormSucceeded(TurnusUpdateForm $form): void
	{
		$this->assertCanManage();
		$this->tryHandleAutosavePartialRequest();
		$this->turnusRepository->updateFromForm($form);
		$this->finishAutosave();
	}

	private function createTurnusFormSucceeded(Form $form): void
	{
		$this->assertCanManage();

		$id = $this->turnusRepository->createEmptyTurnus((int) $this->getUser()->getId());
		$this->changeAuditLogger->logCreated('turnus.update', TurnusTableMap::TABLE_NAME, $id, 'Turnus');
		$this->redirect('update', $id);
	}

	private function turnusStatusA1FormSucceeded(int $id, int $statusA1): void
	{
		$this->assertCanManage();
		if ($id !== $this->turnusId) {
			$this->error('Neplatný turnus.', 400);
		}

		$this->tryHandleAutosavePartialRequest();
		$this->turnusRepository->updateStatusA1($id, $statusA1);
		$this->finishAutosave();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getTurnus(): array
	{
		if ($this->turnus === null) {
			$this->turnus = $this->turnusRepository->findUpdateRow($this->turnusId);
			if ($this->turnus === null) {
				$this->error('Turnus neexistuje.', 404);
			}
		}

		return $this->turnus;
	}

	private function assertCanManage(): void
	{
		if (!$this->getUser()->isAllowed(Resource::TURNUS->value)) {
			$this->error('Prístup zamietnutý', 403);
		}
	}

	private function finishAutosave(): void
	{
		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->redirect('this');
	}

	private function getCurrentYear(): int
	{
		return (int) (new DateTimeImmutable())->format('Y');
	}
}
