<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\TurnusList;

use App\Model\Enum\Acl\Resource;
use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\TurnusRepository;
use App\Model\Utils\Paginator\Paginator;
use Nette\Application\UI\Control;
use Nette\Security\User;

class TurnusListControl extends Control
{
	private const int ITEMS_PER_PAGE = 50;

	private int $page = 1;

	private int $finish = 0;

	private ?int $statusId = null;

	private ?int $countryId = null;

	private ?int $agencyId = null;

	private int $order = 0;

	private int $resultCount = 0;

	/** @var list<array<string, mixed>>|null */
	private ?array $rows = null;

	public function __construct(
		private readonly TurnusRepository $turnusRepository,
		private readonly PaginatorFactory $paginatorFactory,
		private readonly User $user,
	) {
	}

	public function setPage(int $page): static
	{
		$this->page = max(1, $page);

		return $this;
	}

	public function setFilters(int $finish, ?int $statusId, ?int $countryId, ?int $agencyId, int $order): static
	{
		$this->finish = $finish === 1 ? 1 : 0;
		$this->statusId = $statusId !== null && $statusId > 0 ? $statusId : null;
		$this->countryId = $countryId !== null && $countryId > 0 ? $countryId : null;
		$this->agencyId = $agencyId !== null && $agencyId > 0 ? $agencyId : null;
		$this->order = in_array($order, [1, 2, 3, 4], true) ? $order : 0;

		return $this;
	}

	public function render(): void
	{
		if (!$this->user->isAllowed(Resource::TURNUS->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/TurnusListControl.latte');
		$template->rows = $this->getRows();
		$template->paginator = $this->createPaginator();
		$template->finish = $this->finish;
		$template->statusId = $this->statusId;
		$template->countryId = $this->countryId;
		$template->agencyId = $this->agencyId;
		$template->order = $this->order;
		$template->resultCount = $this->resultCount;
		$template->fromOrder = in_array($this->order, [1, 3], true) ? $this->order : 0;
		$template->toOrder = in_array($this->order, [2, 4], true) ? $this->order : 0;
		$template->fromOrderNext = $this->order === 1 ? 3 : 1;
		$template->toOrderNext = $this->order === 2 ? 4 : 2;
		$template->canOpenFamily = $this->user->isAllowed(Resource::FAMILY->value);
		$template->canOpenBabysitter = $this->user->isAllowed(Resource::BABYSITTER->value);
		$template->canOpenTurnus = $this->user->isAllowed(Resource::TURNUS->value);
		$template->render();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getRows(): array
	{
		if ($this->rows === null) {
			$pageCount = 1;
			$resultCount = 0;
			$this->rows = $this->turnusRepository->findTurnusRows(
				$this->page,
				self::ITEMS_PER_PAGE,
				$this->finish,
				$this->statusId,
				$this->countryId,
				$this->agencyId,
				$this->order,
				$pageCount,
				$resultCount,
			);
			$this->resultCount = max(0, $resultCount);
		}

		return $this->rows;
	}

	private function createPaginator(): Paginator
	{
		$this->getRows();

		return $this->paginatorFactory->create(
			$this->page,
			$this->resultCount,
			self::ITEMS_PER_PAGE,
			'this',
			array_filter(
				[
					'finish' => $this->finish === 1 ? 1 : null,
					'status' => $this->statusId,
					'country' => $this->countryId,
					'agency' => $this->agencyId,
					'order' => $this->order > 0 ? $this->order : null,
				],
				static fn (mixed $value): bool => $value !== null,
			),
		);
	}
}
