<?php declare(strict_types=1);

namespace App\UI\Admin\Control\PartnerFamily\PartnerFamilyList;

interface PartnerFamilyListControlFactory
{
	public function create(int $partnerId): PartnerFamilyListControl;
}
