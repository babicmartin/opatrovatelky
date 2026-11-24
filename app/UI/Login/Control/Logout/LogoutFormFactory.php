<?php declare(strict_types=1);

namespace App\UI\Login\Control\Logout;

use Nette\Application\UI\Form;

/**
 * The logout form is rendered by the administration layout and submitted to the login presenter,
 * so both sides build it here to share one definition.
 */
final class LogoutFormFactory
{
	public function create(string $action): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->setAction($action);
		$form->addSubmit('send', 'Odhlásiť sa');

		return $form;
	}
}
