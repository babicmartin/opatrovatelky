<?php declare(strict_types=1);

namespace App\UI\Admin\Control\PartnerFamily\PartnerBabysitterDashboard;

interface PartnerBabysitterDashboardControlFactory
{
	public function create(int $partnerId, string $type, ?int $familyId): PartnerBabysitterDashboardControl;
}
