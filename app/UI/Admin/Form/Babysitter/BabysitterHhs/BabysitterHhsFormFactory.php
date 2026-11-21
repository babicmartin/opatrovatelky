<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Babysitter\BabysitterHhs;

use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;

final readonly class BabysitterHhsFormFactory
{
	public function __construct(private BaseFormFactory $baseFormFactory)
	{
	}

	/** @param array<string, mixed> $data */
	public function create(array $data): Form
	{
		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'js-autosave-form')->setAttribute('data-autosave-context', 'babysitter.hhs');
		$form->addHidden('id', (string) $data['id']);
		$form->addText('hhs_start_date', 'Nástup od')->setHtmlType('date')
			->setHtmlAttribute('class', 'form-control js-autosave-control')->setDefaultValue($data['hhs_start_date']?->format('Y-m-d'));
		$form->addSelect('hhs_night_work', 'Práca v noci', [1 => 'Áno', 0 => 'Nie'])->setPrompt('—')
			->setHtmlAttribute('class', 'form-select js-autosave-control')->setDefaultValue($data['hhs_night_work']);
		$form->addTextArea('hhs_additional_notes', 'Dodatočné poznámky')
			->setHtmlAttribute('class', 'form-control js-autosave-control')->setDefaultValue($data['hhs_additional_notes']);
		$form->addText('hhs_monthly_costs', 'Mesačné náklady')->setHtmlAttribute('inputmode', 'decimal')
			->setHtmlAttribute('class', 'form-control js-autosave-control')->setDefaultValue($data['hhs_monthly_costs']);
		$form->addText('hhs_travel_costs', 'Cestovné náklady')->setHtmlAttribute('inputmode', 'decimal')
			->setHtmlAttribute('class', 'form-control js-autosave-control')->setDefaultValue($data['hhs_travel_costs']);
		$form->addText('hhs_holiday_supplement', 'Príplatky počas sviatkov')
			->setHtmlAttribute('class', 'form-control js-autosave-control')->setDefaultValue($data['hhs_holiday_supplement']);
		$form->addText('hhs_created_by_name', 'Vytvoril')
			->setHtmlAttribute('class', 'form-control js-autosave-control')->setDefaultValue($data['hhs_created_by_name']);
		$form->addText('hhs_contact_phone', 'Telefónny kontakt')->setHtmlType('tel')
			->setHtmlAttribute('class', 'form-control js-autosave-control')->setDefaultValue($data['hhs_contact_phone']);
		return $form;
	}
}
