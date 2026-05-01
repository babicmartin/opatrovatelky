<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\FamilyOffcanvas;

interface FamilyOffcanvasControlFactory
{
	public function create(): FamilyOffcanvasControl;
}
