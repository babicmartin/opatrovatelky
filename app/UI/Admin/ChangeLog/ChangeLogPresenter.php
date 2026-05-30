<?php declare(strict_types=1);

namespace App\UI\Admin\ChangeLog;

use App\Model\Enum\Acl\Resource;
use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\ChangeLogRepository;
use App\UI\Admin\AdminPresenter;

final class ChangeLogPresenter extends AdminPresenter
{
	private const int ITEMS_PER_PAGE = 50;

	public function __construct(
		private readonly ChangeLogRepository $changeLogRepository,
		private readonly PaginatorFactory $paginatorFactory,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::CHANGE_LOG->value;
	}

	public function actionDefault(?int $page = null): void
	{
		$filters = $this->readFilters();
		$currentPage = max(1, $page ?? 1);
		$pageCount = 1;
		$this->template->rows = $this->changeLogRepository->findRows($currentPage, self::ITEMS_PER_PAGE, $pageCount, $filters);
		$this->template->filters = $filters;
		$this->template->userOptions = $this->changeLogRepository->findUserOptions();
		$this->template->sectionOptions = $this->changeLogRepository->getSectionOptions();
		$this->template->actionOptions = $this->changeLogRepository->getActionOptions();
		$this->template->paginator = $this->paginatorFactory->createFromPageCount(
			$currentPage,
			$pageCount,
			self::ITEMS_PER_PAGE,
			'this',
			$this->createPaginatorParams($filters),
		);
	}

	/**
	 * @return array{user:string,dateFrom:string,dateTo:string,section:string,status:string,entity:string,q:string}
	 */
	private function readFilters(): array
	{
		$request = $this->getHttpRequest();

		return [
			'user' => trim((string) ($request->getQuery('user') ?? '')),
			'dateFrom' => trim((string) ($request->getQuery('dateFrom') ?? '')),
			'dateTo' => trim((string) ($request->getQuery('dateTo') ?? '')),
			'section' => trim((string) ($request->getQuery('section') ?? '')),
			'status' => trim((string) ($request->getQuery('status') ?? '')),
			'entity' => trim((string) ($request->getQuery('entity') ?? '')),
			'q' => trim((string) ($request->getQuery('q') ?? '')),
		];
	}

	/**
	 * @param array<string, string> $filters
	 * @return array<string, string>
	 */
	private function createPaginatorParams(array $filters): array
	{
		return array_filter(
			$filters,
			static fn (string $value): bool => $value !== '',
		);
	}
}
