<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\ProjectOffcanvas;

trait ProjectOffcanvasPresenterTrait
{
	private ProjectOffcanvasControlFactory $projectOffcanvasControlFactory;

	public function injectProjectOffcanvasControl(
		ProjectOffcanvasControlFactory $projectOffcanvasControlFactory,
	): void {
		$this->projectOffcanvasControlFactory = $projectOffcanvasControlFactory;
	}

	protected function createComponentProjectOffcanvas(): ProjectOffcanvasControl
	{
		return $this->projectOffcanvasControlFactory->create();
	}
}
