<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\HomepageTurnusList;

interface HomepageTurnusListControlFactory
{
	public function create(): HomepageTurnusListControl;
}
