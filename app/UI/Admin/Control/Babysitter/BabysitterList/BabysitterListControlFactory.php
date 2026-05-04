<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Babysitter\BabysitterList;

interface BabysitterListControlFactory
{
	public function create(): BabysitterListControl;
}
