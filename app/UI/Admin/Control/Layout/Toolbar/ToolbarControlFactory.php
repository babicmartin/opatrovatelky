<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar;

interface ToolbarControlFactory
{
	public function create(): ToolbarControl;
}
