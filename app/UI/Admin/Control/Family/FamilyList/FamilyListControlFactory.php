<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Family\FamilyList;

interface FamilyListControlFactory
{
	public function create(): FamilyListControl;
}
