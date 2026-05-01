<?php declare(strict_types = 1);

namespace App\Model\Form\Service;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;

class FormService
{
	public function addClassToLabel(Form &$form, string $className): void
	{
		foreach ($form->getControls() as $control) {
			/** @var \Nette\Forms\Controls\BaseControl $control */
			$control->getLabelPrototype()->class('form-label');
			if ($control instanceof TextInput) {
				$control->setHtmlAttribute('class', $className);
			}
		}
	}

	public function addClassToInput(Form &$form, string $className): void
	{
		foreach ($form->getControls() as $control) {
			if ($control instanceof TextInput) {
				$control->setHtmlAttribute('class', $className);
			}
		}
	}

	public function addClassToSelectBox(Form &$form, string $className): void
	{
		foreach ($form->getControls() as $control) {
			if ($control instanceof SelectBox) {
				$control->setHtmlAttribute('class', $className);
			}
		}
	}

	public function addClassToTextArea(Form &$form, string $className): void
	{
		foreach ($form->getControls() as $control) {
			if ($control instanceof TextArea) {
				$control->setHtmlAttribute('class', $className);
			}
		}
	}
}