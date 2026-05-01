<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar;

trait ToolbarPresenterTrait
{
	private ToolbarControlFactory $toolbarControlFactory;

	public function injectToolbarControl(
		ToolbarControlFactory $toolbarControlFactory,
	): void {
		$this->toolbarControlFactory = $toolbarControlFactory;
	}

	protected function createComponentToolbar(): ToolbarControl
	{
		return $this->toolbarControlFactory->create();
	}
}
