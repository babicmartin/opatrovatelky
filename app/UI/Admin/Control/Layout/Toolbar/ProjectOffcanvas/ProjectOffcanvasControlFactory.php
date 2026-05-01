<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\ProjectOffcanvas;

interface ProjectOffcanvasControlFactory
{
	public function create(): ProjectOffcanvasControl;
}
