<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Search;

use Nette\Application\UI\Control;

class SearchControl extends Control
{
	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/SearchControl.latte');
		$template->render();
	}
}
