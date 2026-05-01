<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\UnpaidInvoicesWidget;

interface UnpaidInvoicesWidgetControlFactory
{
	public function create(): UnpaidInvoicesWidgetControl;
}
