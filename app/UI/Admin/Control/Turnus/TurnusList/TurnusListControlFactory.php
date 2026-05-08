<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\TurnusList;

interface TurnusListControlFactory
{
	public function create(): TurnusListControl;
}
