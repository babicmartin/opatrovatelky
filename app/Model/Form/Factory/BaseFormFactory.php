<?php declare(strict_types = 1);

namespace App\Model\Form\Factory;

use Nette\Application\UI\Form;

class BaseFormFactory
{
	public function create(): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->getElementPrototype()->setAttribute('novalidate', 'novalidate');

		return $form;
	}

	public function createDefaultForm(): Form
	{
		$form = new Form();
		$form->addProtection();

		return $form;
	}
}