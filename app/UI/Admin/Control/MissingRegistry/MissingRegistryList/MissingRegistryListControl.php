<?php declare(strict_types=1);

namespace App\UI\Admin\Control\MissingRegistry\MissingRegistryList;

use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\MissingRegistryRepository;
use App\Model\Repository\UserRepository;
use App\Model\Utils\Paginator\Paginator;
use App\UI\Admin\Form\MissingRegistry\MissingRegistryFormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

class MissingRegistryListControl extends Control
{
	private const int ITEMS_PER_PAGE = 20;

	private int $page = 1;

	private int $pageCount = 1;

	/** @var list<array<string, mixed>>|null */
	private ?array $rows = null;

	/** @var array<string, mixed>|null */
	private ?array $newRow = null;

	public function __construct(
		private readonly MissingRegistryRepository $missingRegistryRepository,
		private readonly UserRepository $userRepository,
		private readonly PaginatorFactory $paginatorFactory,
		private readonly MissingRegistryFormFactory $missingRegistryFormFactory,
	) {
	}

	public function setPage(int $page): static
	{
		$this->page = max(1, $page);

		return $this;
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/MissingRegistryListControl.latte');
		$template->newRow = $this->getNewRow();
		$template->rows = $this->getRowsWithoutNewRow();
		$template->paginator = $this->createPaginator();
		$template->render();
	}

	public function handleCreate(): void
	{
		$this->missingRegistryRepository->createEmpty();
		$this->redirect('this');
	}

	public function handleDelete(int $id): void
	{
		$this->missingRegistryRepository->softDelete($id);
		$this->redirect('this');
	}

	/**
	 * @return Multiplier<Form>
	 */
	protected function createComponentRegistryForm(): Multiplier
	{
		return new Multiplier(function (string $id): Form {
			$row = $this->findRowForForm((int) $id);
			if ($row === null) {
				$this->error('Evidencia neexistuje.');
			}

			return $this->missingRegistryFormFactory->create(
				$row,
				$this->userRepository->findSelectOptions(),
				$this->registryFormSucceeded(...),
			);
		});
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function registryFormSucceeded(int $id, array $values): void
	{
		$this->missingRegistryRepository->updateRegistryRow($id, $values);

		if ($this->getPresenter()->isAjax()) {
			$this->getPresenter()->sendJson(['success' => true]);
		}

		$this->redirect('this');
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getRowsWithoutNewRow(): array
	{
		$newRowId = $this->getNewRow()['id'] ?? null;

		return array_values(array_filter(
			$this->getRows(),
			static fn (array $row): bool => $row['id'] !== $newRowId,
		));
	}

	/**
	 * @return list<array<string, mixed>>
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
				$this->getNewRow()['id'] ?? null,
				$pageCount,
			);
			$this->pageCount = max(1, $pageCount);
		}

		return $this->rows;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function getNewRow(): ?array
	{
		if ($this->newRow === null) {
			$this->newRow = $this->missingRegistryRepository->findLastEmptyRow();
		}

		return $this->newRow;
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

	/**
	 * @return array<string, mixed>|null
	 */
	private function findRowForForm(int $id): ?array
	{
		$newRow = $this->getNewRow();
		if ($newRow !== null && $newRow['id'] === $id) {
			return $newRow;
		}

		foreach ($this->getRowsWithoutNewRow() as $row) {
			if ($row['id'] === $id) {
				return $row;
			}
		}

		return null;
	}
}
