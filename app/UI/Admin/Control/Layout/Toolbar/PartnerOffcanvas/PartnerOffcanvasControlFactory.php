<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\PartnerOffcanvas;

interface PartnerOffcanvasControlFactory
{
	public function create(): PartnerOffcanvasControl;
}
