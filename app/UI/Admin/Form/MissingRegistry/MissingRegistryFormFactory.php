<?php declare(strict_types=1);

namespace App\UI\Admin\Form\MissingRegistry;

use App\Model\DTO\MissingRegistry\MissingRegistryInput;
use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Utils\Date\DateService;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * One row of the registry list, saved field by field through autosave.
 */
class MissingRegistryFormFactory
{
	public function __construct(
		private readonly BaseFormFactory $baseFormFactory,
		private readonly DateService $dateService,
	) {
	}

	/**
	 * The hidden `id` only tells autosaveForm.js which row it sends; the server never writes by it.
	 * The caller knows the record and receives just the submitted values.
	 *
	 * @param array<int, string>|null $userOptions null: the user may not choose whose record it is
	 * @param callable(MissingRegistryInput): void $onSuccess
	 */
	public function create(MissingRegistryRecord $record, ?array $userOptions, callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'missing-registry-form js-autosave-form');

		$form->addHidden('id', (string) $record->id);
		if ($userOptions !== null) {
			$form->addSelect('userId', 'Kto', $userOptions)
				->checkDefaultValue(false)
				->setDefaultValue($record->userId)
				->setHtmlAttribute('class', 'form-select updateSelect js-autosave-control');
		}
		$form->addText('dateFrom', 'Od')
			->setDefaultValue($record->dateFrom?->format('d.m.Y') ?? '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addText('dateTo', 'Do')
			->setDefaultValue($record->dateTo?->format('d.m.Y') ?? '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		foreach (MissingType::cases() as $type) {
			// No caption: the template prints the label next to the bare checkbox.
			$form->addCheckbox($type->field())
				->setDefaultValue($record->hasType($type))
				->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		}
		$form->addTextArea('notice', 'Poznámka')
			->setDefaultValue($record->notice)
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h70');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$onSuccess(new MissingRegistryInput(
				isset($values->userId) ? (int) $values->userId : null,
				$this->dateService->tryCreateFromUserInput((string) $values->dateFrom),
				$this->dateService->tryCreateFromUserInput((string) $values->dateTo),
				array_values(array_filter(
					MissingType::cases(),
					static fn (MissingType $type): bool => (bool) $values->{$type->field()},
				)),
				(string) $values->notice,
			));
		};

		return $form;
	}
}
