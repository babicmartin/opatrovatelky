<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Partner\PartnerList;

interface PartnerListControlFactory
{
	public function create(): PartnerListControl;
}
