<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Turnus\TurnusUpdate;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Turnus\TurnusUpdate\TurnusUpdateForm;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Utils\Date\DateService;
use DateTimeImmutable;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class TurnusUpdateFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
		private DateService $dateService,
	) {
	}

	/**
	 * @param array<string, mixed> $turnus
	 * @param array<int, string> $statusOptions
	 * @param array<int, string> $familyOptions
	 * @param array<int, string> $babysitterOptions
	 * @param array<int, string> $userOptions
	 * @param array<int, string> $agencyOptions
	 * @param array<int, string> $partnerOptions
	 * @param array<int, string> $workingStatusOptions
	 * @param array<int, string> $workPositionOptions
	 * @param array<int, string> $invoiceStatusOptions
	 * @param array<int, string> $paymentPeriodOptions
	 * @param array<int, string> $complaintStatusOptions
	 * @param callable(TurnusUpdateForm): void $onSuccess
	 */
	public function create(
		array $turnus,
		array $statusOptions,
		array $familyOptions,
		array $babysitterOptions,
		array $userOptions,
		array $agencyOptions,
		array $partnerOptions,
		array $workingStatusOptions,
		array $workPositionOptions,
		array $invoiceStatusOptions,
		array $paymentPeriodOptions,
		array $complaintStatusOptions,
		bool $showWorkPosition,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::TURNUS->value)) {
			throw new ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'turnus-update-form js-autosave-form');

		$form->addHidden('id', (string) $turnus['id']);
		$form->addSelect('status', 'Status', $statusOptions)
			->setDefaultValue((int) $turnus['status'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('familyId', 'Rodina', $familyOptions)
			->setDefaultValue((int) $turnus['familyId'])
			->setHtmlAttribute('class', 'form-control updateSelectReload js-autosave-control');
		$form->addSelect('babysitterId', 'Opatrovateľka', $babysitterOptions)
			->setDefaultValue((int) $turnus['babysitterId'])
			->setHtmlAttribute('class', 'form-control updateSelectReload js-autosave-control');
		$this->addDate($form, 'dateFrom', 'Nástup', $turnus['dateFrom'] ?? null);
		$this->addDate($form, 'dateTo', 'Ukončenie', $turnus['dateTo'] ?? null);
		$form->addSelect('userId', 'Spracováva', $userOptions)
			->setDefaultValue((int) $turnus['userId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('agencyId', 'Agentúra', $agencyOptions)
			->setDefaultValue((int) $turnus['agencyId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('partnerId', 'Partner', $partnerOptions)
			->setDefaultValue((int) $turnus['partnerId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('workingStatus', 'Status zamestnanca', $workingStatusOptions)
			->setDefaultValue((int) $turnus['workingStatus'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		if ($showWorkPosition) {
			$form->addSelect('workPositionId', 'Pracovná pozícia', $workPositionOptions)
				->setDefaultValue((int) $turnus['workPositionId'])
				->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		} else {
			$form->addHidden('workPositionId', (string) $turnus['workPositionId']);
		}
		$this->addText($form, 'preinvoiceNumber', 'Číslo PFA', $turnus);
		$this->addText($form, 'invoiceNumber', 'Číslo FA', $turnus);
		$form->addSelect('invoiceStatus', 'Status FA', $invoiceStatusOptions)
			->setDefaultValue((int) $turnus['invoiceStatus'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$this->addFloatText($form, 'fee', 'Honorár DLV', $turnus);
		$this->addFloatText($form, 'feeAg', 'Honorár AG', $turnus);
		$this->addFloatText($form, 'feeBk', 'Honorár BK', $turnus);
		$this->addFloatText($form, 'travelCostsArrival', 'Suma - príchod', $turnus);
		$this->addFloatText($form, 'travelCostsDeparture', 'Suma - odchod', $turnus);
		$this->addText($form, 'travelExpenses', 'Cestovné', $turnus);
		$this->addFloatText($form, 'bonus', 'Bonus', $turnus);
		$this->addFloatText($form, 'holiday', 'Sviatky', $turnus);
		$this->addText($form, 'sva', 'SVA', $turnus);
		$this->addFloatText($form, 'commissionComplet', 'Provízia - komplet', $turnus);
		$this->addFloatText($form, 'commissionPartners', 'Partneri', $turnus);
		$form->addSelect('paymentPeriodPartner', 'Platba', $paymentPeriodOptions)
			->setDefaultValue((int) $turnus['paymentPeriodPartner'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$this->addFloatText($form, 'commission4ms', '4MS', $turnus);
		$form->addSelect('paymentPeriod', 'Platba', $paymentPeriodOptions)
			->setDefaultValue((int) $turnus['paymentPeriod'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$this->addFloatText($form, 'remainingPayment', 'Zostávajúca platba', $turnus);
		$form->addTextArea('notice', 'Poznámka')
			->setDefaultValue((string) $turnus['notice'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h150');
		$form->addTextArea('complaint', 'Reklamácia')
			->setDefaultValue((string) $turnus['complaint'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h150');
		$form->addSelect('complaintStatus', 'Status Reklamácie', $complaintStatusOptions)
			->setDefaultValue((int) $turnus['complaintStatus'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::TURNUS->value)) {
				throw new ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new TurnusUpdateForm(
				(int) $values->id,
				(int) $values->status,
				(int) $values->familyId,
				(int) $values->babysitterId,
				$this->dateService->tryCreateFromUserInput((string) $values->dateFrom),
				$this->dateService->tryCreateFromUserInput((string) $values->dateTo),
				(int) $values->userId,
				(int) $values->agencyId,
				(int) $values->partnerId,
				(int) $values->workingStatus,
				(int) $values->workPositionId,
				(string) $values->preinvoiceNumber,
				(string) $values->invoiceNumber,
				(int) $values->invoiceStatus,
				$this->normalizeRequiredFloat((string) $values->fee),
				$this->normalizeRequiredFloat((string) $values->feeAg),
				$this->normalizeRequiredFloat((string) $values->feeBk),
				$this->normalizeRequiredFloat((string) $values->travelCostsArrival),
				$this->normalizeRequiredFloat((string) $values->travelCostsDeparture),
				(string) $values->travelExpenses,
				$this->normalizeRequiredFloat((string) $values->bonus),
				$this->normalizeRequiredFloat((string) $values->holiday),
				(string) $values->sva,
				$this->normalizeRequiredFloat((string) $values->commissionComplet),
				$this->normalizeRequiredFloat((string) $values->commissionPartners),
				(int) $values->paymentPeriodPartner,
				$this->normalizeRequiredFloat((string) $values->commission4ms),
				(int) $values->paymentPeriod,
				$this->normalizeNullableFloat((string) $values->remainingPayment),
				(string) $values->notice,
				(string) $values->complaint,
				(int) $values->complaintStatus,
			));
		};

		return $form;
	}

	/**
	 * @param array<string, mixed> $turnus
	 * @param array<int, string> $statusA1Options
	 * @param callable(int, int): void $onSuccess
	 */
	public function createStatusA1(array $turnus, array $statusA1Options, callable $onSuccess): Form
	{
		if (!$this->user->isAllowed(Resource::TURNUS->value)) {
			throw new ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'turnus-status-a1-form js-autosave-form');
		$form->addHidden('id', (string) $turnus['id']);
		$form->addSelect('statusA1', 'Status A1', $statusA1Options)
			->setDefaultValue((int) $turnus['statusA1'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::TURNUS->value)) {
				throw new ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess((int) $values->id, (int) $values->statusA1);
		};

		return $form;
	}

	/**
	 * @param array<string, mixed> $turnus
	 */
	private function addText(Form $form, string $name, string $label, array $turnus): void
	{
		$form->addText($name, $label)
			->setDefaultValue((string) ($turnus[$name] ?? ''))
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
	}

	/**
	 * @param array<string, mixed> $turnus
	 */
	private function addFloatText(Form $form, string $name, string $label, array $turnus): void
	{
		$form->addText($name, $label)
			->setDefaultValue((string) ($turnus[$name] ?? ''))
			->addRule(
				static fn (BaseControl $control): bool => self::isFloatInput((string) $control->getValue()),
				'Zadajte číslo s bodkou alebo čiarkou.',
			)
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
	}

	private function addDate(Form $form, string $name, string $label, mixed $value): void
	{
		$form->addText($name, $label)
			->setDefaultValue($value instanceof DateTimeImmutable ? $value->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
	}

	private function normalizeRequiredFloat(string $value): float
	{
		$normalized = self::normalizeFloatInput($value);

		return $normalized === '' ? 0.0 : (float) $normalized;
	}

	private function normalizeNullableFloat(string $value): ?float
	{
		$normalized = self::normalizeFloatInput($value);

		return $normalized === '' ? null : (float) $normalized;
	}

	private static function isFloatInput(string $value): bool
	{
		$normalized = self::normalizeFloatInput($value);

		return $normalized === '' || is_numeric($normalized);
	}

	private static function normalizeFloatInput(string $value): string
	{
		$value = trim(str_replace([' ', "\xc2\xa0"], '', $value));

		return str_replace(',', '.', $value);
	}
}
