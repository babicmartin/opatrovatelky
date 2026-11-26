<?php declare(strict_types=1);

namespace App\UI\Admin\Control\MissingRegistry\MissingCalendar;

use App\Model\DTO\MissingRegistry\MissingRegistryInput;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Service\MissingRegistry\MissingCalendarBuilder;
use App\Model\Service\MissingRegistry\MissingRegistryAccessPolicy;
use App\Model\Service\MissingRegistry\MissingRegistryDayCounter;
use App\Model\Service\MissingRegistry\MissingRegistryException;
use App\Model\Service\MissingRegistry\MissingRegistryWriteService;
use App\Model\Utils\Date\DateService;
use App\Model\Utils\Date\MonthService;
use App\UI\Admin\Form\MissingRegistry\MissingCalendarFormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Psr\Clock\ClockInterface;

/**
 * Team absence calendar of one month: a row per day, a column per person.
 *
 * Records are added, changed and deleted in a dialog. The forms are sent with fetch and the
 * calendar is redrawn as a snippet; without JavaScript the same requests end with a redirect.
 * Every entry point checks the module right itself and writes only through MissingRegistryWriteService,
 * which decides whose records the current user may touch.
 */
class MissingCalendarControl extends Control
{
	public function __construct(
		private readonly int $year,
		private readonly int $month,
		private readonly MissingCalendarBuilder $calendarBuilder,
		private readonly MissingRegistryWriteService $writeService,
		private readonly MissingRegistryAccessPolicy $policy,
		private readonly MissingRegistryDayCounter $dayCounter,
		private readonly MissingCalendarFormFactory $formFactory,
		private readonly MonthService $monthService,
		private readonly DateService $dateService,
		private readonly ClockInterface $clock,
	) {
	}

	public function render(): void
	{
		$this->assertCanView();

		$first = $this->dateService->getFirstDayOfMonthByYearDate($this->year, $this->month);
		$previous = $this->dateService->subtractMonth($first);
		$next = $this->dateService->addMonth($first);
		$now = $this->clock->now();

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/MissingCalendarControl.latte');
		$template->calendar = $this->calendarBuilder->build($this->year, $this->month);
		$template->types = MissingType::cases();
		$template->months = $this->monthService->getMonths();
		$template->previous = ['year' => (int) $previous->format('Y'), 'month' => (int) $previous->format('n')];
		$template->next = ['year' => (int) $next->format('Y'), 'month' => (int) $next->format('n')];
		$template->today = ['year' => (int) $now->format('Y'), 'month' => (int) $now->format('n')];
		$template->currentUserId = $this->policy->getCurrentUserId();
		$template->workingDayTypes = $this->getTypeLabels(true);
		$template->calendarDayTypes = $this->getTypeLabels(false);
		$template->render();
	}

	/**
	 * Day counts of a range for the dialog, so the counting rules live only on the server.
	 */
	public function handlePreview(string $from = '', string $to = ''): void
	{
		$this->assertCanView();

		$dateFrom = $this->dateService->tryCreateFromUserInput($from);
		$dateTo = $this->dateService->tryCreateFromUserInput($to) ?? $dateFrom;
		if (
			$dateFrom === null
			|| $dateTo === null
			|| $dateTo < $dateFrom
			|| (int) $dateFrom->diff($dateTo)->days >= MissingRegistryWriteService::MAX_RANGE_DAYS
		) {
			$this->getPresenter()->sendJson(['success' => false]);
		}

		$counts = $this->dayCounter->countRange($dateFrom, $dateTo);
		$this->getPresenter()->sendJson([
			'success' => true,
			'calendarDays' => $counts['calendarDays'],
			'workingDays' => $counts['workingDays'],
			'weekendDays' => $counts['weekendDays'],
			'holidays' => array_values($counts['holidays']),
		]);
	}

	protected function createComponentEntryForm(): Form
	{
		$this->assertCanView();

		$form = $this->formFactory->createEntryForm(
			$this->policy->canManageAll() ? $this->policy->getAssignableUsers() : null,
			$this->entryFormSucceeded(...),
		);
		$form->onError[] = $this->formFailed(...);

		return $form;
	}

	protected function createComponentDeleteForm(): Form
	{
		$this->assertCanView();

		$form = $this->formFactory->createDeleteForm($this->deleteFormSucceeded(...));
		$form->onError[] = $this->formFailed(...);

		return $form;
	}

	private function entryFormSucceeded(Form $form, int $id, MissingRegistryInput $input): void
	{
		$this->assertCanView();

		try {
			if ($id > 0) {
				$this->writeService->update($id, $input);
				$message = 'Neprítomnosť bola upravená.';
			} else {
				$this->writeService->create($input);
				$message = 'Neprítomnosť bola pridaná.';
			}
		} catch (MissingRegistryException $e) {
			if ($e->isInvalidInput()) {
				$form->addError($e->getMessage());

				return;
			}

			$this->error($e->getMessage(), $e->getCode());
		}

		$this->finish($message);
	}

	private function deleteFormSucceeded(int $id): void
	{
		$this->assertCanView();

		try {
			$this->writeService->delete($id);
		} catch (MissingRegistryException $e) {
			$this->error($e->getMessage(), $e->getCode());
		}

		$this->finish('Neprítomnosť bola odstránená.');
	}

	/**
	 * After a change: redraw the calendar for a fetch request, redirect otherwise.
	 */
	private function finish(string $message): void
	{
		$presenter = $this->getPresenter();
		if ($presenter->isAjax()) {
			$presenter->payload->success = true;
			$presenter->payload->message = $message;
			$this->redrawControl();

			return;
		}

		$presenter->flashMessage($message, 'success');
		$this->redirect('this');
	}

	private function formFailed(Form $form): void
	{
		$presenter = $this->getPresenter();
		if ($presenter->isAjax()) {
			$presenter->payload->success = false;
			$presenter->payload->errors = array_map(strval(...), $form->getErrors());
			$presenter->sendPayload();
		}

		foreach ($form->getErrors() as $error) {
			$presenter->flashMessage((string) $error, 'danger');
		}
		$this->redirect('this');
	}

	private function assertCanView(): void
	{
		if (!$this->policy->canView()) {
			$this->error('Prístup zamietnutý', 403);
		}
	}

	/**
	 * @return list<string>
	 */
	private function getTypeLabels(bool $workingDaysOnly): array
	{
		return array_values(array_map(
			static fn (MissingType $type): string => $type->label(),
			array_filter(
				MissingType::cases(),
				static fn (MissingType $type): bool => $type->countsWorkingDaysOnly() === $workingDaysOnly,
			),
		));
	}
}
