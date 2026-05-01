<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\HomepageTurnusList;

trait HomepageTurnusListPresenterTrait
{
	private HomepageTurnusListControlFactory $homepageTurnusListControlFactory;

	public function injectHomepageTurnusListControlFactory(
		HomepageTurnusListControlFactory $homepageTurnusListControlFactory,
	): void {
		$this->homepageTurnusListControlFactory = $homepageTurnusListControlFactory;
	}

	protected function createComponentHomepageTurnusList(): HomepageTurnusListControl
	{
		return $this->homepageTurnusListControlFactory->create();
	}
}
