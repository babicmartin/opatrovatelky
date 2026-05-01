<?php declare(strict_types=1);

namespace App\UI\Admin\Turnus;

use App\Model\Enum\Acl\Resource;
use App\Model\Repository\TurnusRepository;
use App\UI\Admin\AdminPresenter;
use DateTimeImmutable;

class TurnusPresenter extends AdminPresenter
{
	private const int FIRST_MONTH_YEAR = 2023;
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

	public function __construct(
		private readonly TurnusRepository $turnusRepository,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::TURNUS->value;
	}

	public function actionDefault(?int $status = null): void
	{
		$this->template->status = $status;
	}

	public function actionUpdate(int $id): void
	{
		$this->template->id = $id;
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

	private function getCurrentYear(): int
	{
		return (int) (new DateTimeImmutable())->format('Y');
	}
}
