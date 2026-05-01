<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\FamilyOffcanvas;

trait FamilyOffcanvasPresenterTrait
{
	private FamilyOffcanvasControlFactory $familyOffcanvasControlFactory;

	public function injectFamilyOffcanvasControl(
		FamilyOffcanvasControlFactory $familyOffcanvasControlFactory,
	): void {
		$this->familyOffcanvasControlFactory = $familyOffcanvasControlFactory;
	}

	protected function createComponentFamilyOffcanvas(): FamilyOffcanvasControl
	{
		return $this->familyOffcanvasControlFactory->create();
	}
}
