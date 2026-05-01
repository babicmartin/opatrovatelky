<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar;

use Nette\Application\UI\Control;

class ToolbarControl extends Control
{
	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/ToolbarControl.latte');
		$template->render();
	}
}
