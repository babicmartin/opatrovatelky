<?php declare(strict_types=1);

namespace App\UI\Admin\Form\MissingRegistry;

use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class MissingRegistryFormFactory
{
	public function __construct(
		private readonly BaseFormFactory $baseFormFactory,
	) {
	}

	/**
	 * @param array<string, mixed> $row
	 * @param array<int, string> $userOptions
	 * @param callable(int, array<string, mixed>): void $onSuccess
	 */
	public function create(array $row, array $userOptions, callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'missing-registry-form js-autosave-form');

		$form->addHidden('id', (string) $row['id']);
		$form->addSelect('userId', 'Kto', $userOptions)
			->setDefaultValue($row['userId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addText('dateFrom', 'Od')
			->setDefaultValue($row['dateFrom'] instanceof \DateTimeImmutable ? $row['dateFrom']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addText('dateTo', 'Do')
			->setDefaultValue($row['dateTo'] instanceof \DateTimeImmutable ? $row['dateTo']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addCheckbox('typePn')->setDefaultValue($row['typePn'])->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		$form->addCheckbox('typeOcr')->setDefaultValue($row['typeOcr'])->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		$form->addCheckbox('typeLekar')->setDefaultValue($row['typeLekar'])->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		$form->addCheckbox('typeSviatok')->setDefaultValue($row['typeSviatok'])->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		$form->addCheckbox('typeZastup')->setDefaultValue($row['typeZastup'])->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		$form->addCheckbox('typeSluzba')->setDefaultValue($row['typeSluzba'])->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		$form->addCheckbox('typeDovolenka')->setDefaultValue($row['typeDovolenka'])->setHtmlAttribute('class', 'updateCheckbox js-autosave-control');
		$form->addTextArea('notice', 'Poznámka')
			->setDefaultValue($row['notice'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h70');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$onSuccess((int) $values->id, (array) $values);
		};

		return $form;
	}
}
