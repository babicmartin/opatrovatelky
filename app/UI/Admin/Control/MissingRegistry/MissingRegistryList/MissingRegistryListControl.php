<?php declare(strict_types=1);

namespace App\UI\Admin\Control\MissingRegistry\MissingRegistryList;

use App\Model\DTO\MissingRegistry\MissingRegistryInput;
use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\MissingRegistryRepository;
use App\Model\Repository\UserRepository;
use App\Model\Service\MissingRegistry\MissingRegistryAccessPolicy;
use App\Model\Service\MissingRegistry\MissingRegistryException;
use App\Model\Service\MissingRegistry\MissingRegistryWriteService;
use App\Model\Utils\Paginator\Paginator;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Form\ConfirmedCreateFormFactory;
use App\UI\Admin\Form\ConfirmedDeleteFormFactory;
use App\UI\Admin\Form\MissingRegistry\MissingRegistryFormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

/**
 * Paged list of the absence registry. Everybody sees every row; a row is a form only for
 * the user who may change it (MissingRegistryAccessPolicy), the others see it as text.
 */
class MissingRegistryListControl extends Control
{
	private const int ITEMS_PER_PAGE = 20;

	private int $page = 1;

	private int $pageCount = 1;

	/** @var list<MissingRegistryRecord>|null */
	private ?array $rows = null;

	private MissingRegistryRecord|false|null $newRow = false;

	/** @var array<int, string>|null */
	private ?array $userOptions = null;

	public function __construct(
		private readonly MissingRegistryRepository $missingRegistryRepository,
		private readonly UserRepository $userRepository,
		private readonly PaginatorFactory $paginatorFactory,
		private readonly MissingRegistryFormFactory $missingRegistryFormFactory,
		private readonly ConfirmedCreateFormFactory $confirmedCreateFormFactory,
		private readonly ConfirmedDeleteFormFactory $confirmedDeleteFormFactory,
		private readonly MissingRegistryWriteService $writeService,
		private readonly MissingRegistryAccessPolicy $policy,
	) {
	}

	public function setPage(int $page): static
	{
		$this->page = max(1, $page);

		return $this;
	}

	public function render(): void
	{
		$this->assertCanView();

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/MissingRegistryListControl.latte');
		$template->newRow = $this->getNewRow();
		$template->rows = $this->getRows();
		$template->paginator = $this->createPaginator();
		$template->types = MissingType::cases();
		$template->userNames = $this->getUserOptions();
		$template->editableIds = $this->getEditableIds();
		$template->render();
	}

	public function handleCreate(): void
	{
		$this->assertCanView();
		$this->redirect('this');
	}

	/** @return Multiplier<Form> */
	protected function createComponentDeleteRegistryForm(): Multiplier
	{
		return new Multiplier(function (string $id): Form {
			$record = $this->getEditableRecord($id);

			return $this->confirmedDeleteFormFactory->create(
				'Naozaj chcete odstrániť evidenciu neprítomnosti?',
				function () use ($record): void {
					$this->deleteRegistryFormSucceeded($record->id);
				},
			);
		});
	}

	/**
	 * @return Multiplier<Form>
	 */
	protected function createComponentRegistryForm(): Multiplier
	{
		return new Multiplier(function (string $id): Form {
			$record = $this->getEditableRecord($id);

			return $this->missingRegistryFormFactory->create(
				$record,
				$this->policy->canManageAll() ? $this->getUserOptions() : null,
				function (MissingRegistryInput $input) use ($record): void {
					$this->registryFormSucceeded($record->id, $input);
				},
			);
		});
	}

	protected function createComponentCreateMissingRegistryForm(): Form
	{
		$this->assertCanView();

		return $this->confirmedCreateFormFactory->create(
			'Pridať novú evidenciu',
			'Naozaj chcete vytvoriť novú evidenciu neprítomnosti?',
			$this->createMissingRegistryFormSucceeded(...),
			'margin:0px 0px 20px 0;',
		);
	}

	private function createMissingRegistryFormSucceeded(Form $form): void
	{
		$this->run(fn () => $this->writeService->createEmpty());
		$this->redirect('this');
	}

	private function deleteRegistryFormSucceeded(int $id): void
	{
		$this->run(fn () => $this->writeService->delete($id));
		$this->redirect('this');
	}

	/**
	 * $id is the record the form was built for, verified on the server. The hidden `id` field is not read.
	 */
	private function registryFormSucceeded(int $id, MissingRegistryInput $input): void
	{
		$presenter = $this->getPresenter();
		if ($presenter instanceof AdminPresenter) {
			$presenter->tryHandleAutosavePartialRequest($id);
		}

		$this->run(fn () => $this->writeService->update($id, $input, requireComplete: false));

		if ($presenter->isAjax()) {
			$presenter->sendJson(['success' => true]);
		}

		$this->redirect('this');
	}

	/**
	 * Record named by a Multiplier key, only when the current user may change it.
	 */
	private function getEditableRecord(string $id): MissingRegistryRecord
	{
		if (!ctype_digit($id)) {
			$this->error('Evidencia neexistuje.');
		}

		return $this->run(fn (): MissingRegistryRecord => $this->writeService->getEditable((int) $id));
	}

	/**
	 * Runs a registry operation and turns its refusal into the matching HTTP error.
	 *
	 * @template T
	 * @param callable(): T $operation
	 * @return T
	 */
	private function run(callable $operation): mixed
	{
		try {
			return $operation();
		} catch (MissingRegistryException $e) {
			$this->error($e->getMessage(), $e->getCode());
		}
	}

	private function assertCanView(): void
	{
		if (!$this->policy->canView()) {
			$this->error('Prístup zamietnutý', 403);
		}
	}

	/**
	 * @return list<MissingRegistryRecord>
	 */
	private function getRows(): array
	{
		if ($this->rows === null) {
			/** @var int<1, max> $itemsPerPage */
			$itemsPerPage = self::ITEMS_PER_PAGE;
			/** @var int<1, max> $page */
			$page = max(1, $this->page);
			$pageCount = 1;
			$this->rows = $this->missingRegistryRepository->findVisibleRows(
				$page,
				$itemsPerPage,
				$this->getNewRow()?->id,
				$pageCount,
			);
			$this->pageCount = max(1, $pageCount);
		}

		return $this->rows;
	}

	/**
	 * The unfinished row offered as "Nový záznam": any for somebody who manages all records, otherwise the own one.
	 */
	private function getNewRow(): ?MissingRegistryRecord
	{
		if ($this->newRow === false) {
			$this->newRow = $this->missingRegistryRepository->findLastEmptyRow(
				$this->policy->canManageAll() ? null : $this->policy->getCurrentUserId(),
			);
		}

		return $this->newRow;
	}

	/**
	 * @return array<int, true> ids of the shown records the current user may change
	 */
	private function getEditableIds(): array
	{
		$ids = [];
		foreach ([$this->getNewRow(), ...$this->getRows()] as $record) {
			if ($record !== null && $this->policy->canEdit($record)) {
				$ids[$record->id] = true;
			}
		}

		return $ids;
	}

	/**
	 * @return array<int, string>
	 */
	private function getUserOptions(): array
	{
		return $this->userOptions ??= $this->userRepository->findSelectOptions();
	}

	private function createPaginator(): Paginator
	{
		$this->getRows();

		return $this->paginatorFactory->createFromPageCount(
			$this->page,
			$this->pageCount,
			self::ITEMS_PER_PAGE,
			'this',
		);
	}
}
