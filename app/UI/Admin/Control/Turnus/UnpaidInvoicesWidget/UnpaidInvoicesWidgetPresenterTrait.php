<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\UnpaidInvoicesWidget;

trait UnpaidInvoicesWidgetPresenterTrait
{
	private UnpaidInvoicesWidgetControlFactory $unpaidInvoicesWidgetControlFactory;

	public function injectUnpaidInvoicesWidgetControlFactory(
		UnpaidInvoicesWidgetControlFactory $unpaidInvoicesWidgetControlFactory,
	): void {
		$this->unpaidInvoicesWidgetControlFactory = $unpaidInvoicesWidgetControlFactory;
	}

	protected function createComponentUnpaidInvoicesWidget(): UnpaidInvoicesWidgetControl
	{
		return $this->unpaidInvoicesWidgetControlFactory->create();
	}
}
