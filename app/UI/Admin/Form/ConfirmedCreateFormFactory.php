<?php declare(strict_types=1);

namespace App\UI\Admin\Form;

use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Utils\Json;

final readonly class ConfirmedCreateFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
	) {
	}

	public function create(
		string $buttonText,
		string $confirmMessage,
		callable $onSuccess,
		string $style = 'margin:0 0 20px 0;',
	): Form {
		$form = $this->baseFormFactory->create();
		$submit = $form->addSubmit('create', $buttonText)
			->setHtmlAttribute('class', 'btn btn-success btn-sm')
			->setHtmlAttribute('onclick', 'return confirm(' . Json::encode($confirmMessage) . ');');

		if ($style !== '') {
			$submit->setHtmlAttribute('style', $style);
		}

		$form->onSuccess[] = $onSuccess;

		return $form;
	}
}
