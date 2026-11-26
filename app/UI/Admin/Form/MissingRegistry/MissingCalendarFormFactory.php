<?php declare(strict_types=1);

namespace App\UI\Admin\Form\MissingRegistry;

use App\Model\DTO\MissingRegistry\MissingRegistryInput;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Service\MissingRegistry\MissingRegistryWriteService;
use App\Model\Utils\Date\DateService;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * Forms of the dialog in the team absence calendar. One form serves every record, so the record
 * is named by the hidden `id`; the receiver must load it through MissingRegistryWriteService,
 * which checks that the current user may change it.
 */
final readonly class MissingCalendarFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private DateService $dateService,
	) {
	}

	/**
	 * @param array<int, string>|null $userOptions null: the record always belongs to the current user
	 * @param callable(Form, int, MissingRegistryInput): void $onSuccess form, record id (0 = new record), values
	 */
	public function createEntryForm(?array $userOptions, callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();

		$form->addHidden('id', '0')
			->addRule($form::Pattern, 'Neplatný záznam.', '\d{1,9}');
		if ($userOptions !== null) {
			$form->addSelect('userId', 'Kto', $userOptions)
				->setRequired('Vyberte, koho sa neprítomnosť týka.')
				->setHtmlAttribute('class', 'form-select');
		}
		$form->addText('dateFrom', 'Od')
			->setHtmlType('date')
			->setRequired('Zadajte dátum „Od“.')
			->setHtmlAttribute('class', 'form-control');
		$form->addText('dateTo', 'Do')
			->setHtmlType('date')
			->setHtmlAttribute('class', 'form-control');
		$form->addCheckboxList('types', 'Typ', MissingType::options())
			->setRequired('Vyberte aspoň jeden typ.');
		$form->addTextArea('notice', 'Poznámka')
			->setMaxLength(MissingRegistryWriteService::MAX_NOTICE_LENGTH)
			->setHtmlAttribute('rows', 3)
			->setHtmlAttribute('class', 'form-control');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'btn btn-success');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$dateFrom = $this->dateService->tryCreateFromUserInput((string) $values->dateFrom);
			$dateTo = $this->dateService->tryCreateFromUserInput((string) $values->dateTo);
			if ($dateFrom === null || ((string) $values->dateTo !== '' && $dateTo === null)) {
				$form->addError('Dátum nie je platný.');

				return;
			}

			$onSuccess($form, (int) $values->id, new MissingRegistryInput(
				isset($values->userId) ? (int) $values->userId : null,
				$dateFrom,
				$dateTo,
				array_map(MissingType::from(...), array_values((array) $values->types)),
				(string) $values->notice,
			));
		};

		return $form;
	}

	/**
	 * @param callable(int): void $onSuccess record id
	 */
	public function createDeleteForm(callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();

		$form->addHidden('id', '0')
			->addRule($form::Pattern, 'Neplatný záznam.', '\d{1,9}');
		$form->addSubmit('delete', 'Odstrániť');

		$form->onSuccess[] = static function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$onSuccess((int) $values->id);
		};

		return $form;
	}
}
