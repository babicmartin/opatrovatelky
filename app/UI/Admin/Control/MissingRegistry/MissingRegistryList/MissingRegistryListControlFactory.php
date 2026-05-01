<?php declare(strict_types=1);

namespace App\UI\Admin\Control\MissingRegistry\MissingRegistryList;

interface MissingRegistryListControlFactory
{
	public function create(): MissingRegistryListControl;
}
