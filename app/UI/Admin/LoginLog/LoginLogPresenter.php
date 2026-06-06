<?php declare(strict_types=1);

namespace App\UI\Admin\LoginLog;

use App\Model\Enum\Acl\Resource;
use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\SecurityAuditLogRepository;
use App\UI\Admin\AdminPresenter;

final class LoginLogPresenter extends AdminPresenter
{
	private const int ITEMS_PER_PAGE = 50;

	public function __construct(
		private readonly SecurityAuditLogRepository $securityAuditLogRepository,
		private readonly PaginatorFactory $paginatorFactory,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::LOGIN_LOG->value;
	}

	public function actionDefault(?int $page = null): void
	{
		$filters = $this->readFilters();
		$currentPage = max(1, $page ?? 1);
		$pageCount = 1;
		$this->template->rows = $this->securityAuditLogRepository->findLoginRows($currentPage, self::ITEMS_PER_PAGE, $pageCount, $filters);
		$this->template->filters = $filters;
		$this->template->eventOptions = $this->securityAuditLogRepository->getLoginEventOptions();
		$this->template->userOptions = $this->securityAuditLogRepository->findLoginUserOptions();
		$this->template->paginator = $this->paginatorFactory->createFromPageCount(
			$currentPage,
			$pageCount,
			self::ITEMS_PER_PAGE,
			'this',
			$this->createPaginatorParams($filters),
		);
	}

	/**
	 * @return array{event:string,user:string,dateFrom:string,dateTo:string,ip:string,q:string}
	 */
	private function readFilters(): array
	{
		$request = $this->getHttpRequest();

		return [
			'event' => trim((string) ($request->getQuery('event') ?? '')),
			'user' => trim((string) ($request->getQuery('user') ?? '')),
			'dateFrom' => trim((string) ($request->getQuery('dateFrom') ?? '')),
			'dateTo' => trim((string) ($request->getQuery('dateTo') ?? '')),
			'ip' => trim((string) ($request->getQuery('ip') ?? '')),
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
