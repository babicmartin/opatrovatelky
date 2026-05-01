<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Menu;

trait MenuPresenterTrait
{
	private MenuControlFactory $menuControlFactory;

	public function injectMenuControl(
		MenuControlFactory $menuControlFactory,
	): void {
		$this->menuControlFactory = $menuControlFactory;
	}

	protected function createComponentMenu(): MenuControl
	{
		return $this->menuControlFactory->create();
	}
}
