<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Worker\WorkerList;

trait WorkerListPresenterTrait
{
	private WorkerListControlFactory $workerListControlFactory;

	public function injectWorkerListControlFactory(
		WorkerListControlFactory $workerListControlFactory,
	): void {
		$this->workerListControlFactory = $workerListControlFactory;
	}

	protected function createComponentWorkerList(): WorkerListControl
	{
		$control = $this->workerListControlFactory->create();
		$control->setPage($this->getParameter('page') !== null ? (int) $this->getParameter('page') : 1);

		$query = $this->getHttpRequest()->getQuery();
		$firstLetterRaw = $query['first-letter'] ?? null;
		$workingStatusRaw = $query['working-status'] ?? null;

		$control->setFilters(
			$this->getParameter('country') !== null ? (int) $this->getParameter('country') : null,
			$this->getParameter('language') !== null ? (int) $this->getParameter('language') : null,
			is_string($workingStatusRaw) && $workingStatusRaw !== '' ? (int) $workingStatusRaw : null,
			$this->getParameter('gender') !== null ? (int) $this->getParameter('gender') : null,
			$this->getParameter('driver') !== null ? (int) $this->getParameter('driver') : null,
			$this->getParameter('smoker') !== null ? (int) $this->getParameter('smoker') : null,
			$this->getParameter('agency') !== null ? (int) $this->getParameter('agency') : null,
			$this->getParameter('status') !== null ? (int) $this->getParameter('status') : null,
			is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null,
		);

		return $control;
	}
}
