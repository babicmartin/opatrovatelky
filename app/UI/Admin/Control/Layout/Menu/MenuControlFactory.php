<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Menu;

interface MenuControlFactory
{
	public function create(): MenuControl;
}
