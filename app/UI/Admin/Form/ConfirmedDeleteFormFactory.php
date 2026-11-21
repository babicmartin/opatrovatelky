<?php declare(strict_types=1);

namespace App\UI\Admin\Form;

use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\Json;

final readonly class ConfirmedDeleteFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
	) {
	}

	/** @param callable(): void $onSuccess */
	public function create(string $confirmMessage, callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();
		$form->addSubmit('delete', 'Odstrániť')
			->setHtmlAttribute('class', 'btn btn-outline-danger btn-sm')
			->setHtmlAttribute('onclick', 'return confirm(' . Json::encode($confirmMessage) . ');');
		$form->onSuccess[] = $onSuccess;
		$form->onError[] = static function (): never {
			throw new ForbiddenRequestException('Potvrdenie odstránenia je neplatné.');
		};

		return $form;
	}
}
