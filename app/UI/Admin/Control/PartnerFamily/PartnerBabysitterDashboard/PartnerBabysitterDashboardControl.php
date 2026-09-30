<?php declare(strict_types=1);

namespace App\UI\Admin\Control\PartnerFamily\PartnerBabysitterDashboard;

use App\Model\DTO\Partner\PartnerBabysitterDashboard;
use App\Model\Enum\Acl\Resource;
use App\Model\Enum\Partner\EngagementType;
use App\Model\Service\Partner\PartnerFamilyEngagementService;
use Nette\Application\UI\Control;
use Nette\Security\User;

/**
 * Babysitters of a partner (optionally of one family) with their care records and summary KPIs.
 */
class PartnerBabysitterDashboardControl extends Control
{
	private ?PartnerBabysitterDashboard $dashboard = null;


	public function __construct(
		private readonly int $partnerId,
		private readonly string $type,
		private readonly ?int $familyId,
		private readonly PartnerFamilyEngagementService $engagementService,
		private readonly User $user,
	) {
	}


	public function render(?string $familyName = null): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/PartnerBabysitterDashboardControl.latte');
		$template->partnerId = $this->partnerId;
		$template->familyId = $this->familyId;
		$template->familyName = $familyName;
		$template->dashboard = $this->getDashboard();
		$template->types = [EngagementType::ALL, EngagementType::ACTIVE, EngagementType::FINISHED];
		$template->canOpenFamily = $this->user->isAllowed(Resource::FAMILY->value);
		$template->canOpenBabysitter = $this->user->isAllowed(Resource::BABYSITTER->value);
		$template->canOpenTurnus = $this->user->isAllowed(Resource::TURNUS->value);
		$template->render();
	}


	private function getDashboard(): PartnerBabysitterDashboard
	{
		return $this->dashboard ??= $this->engagementService->getBabysitterDashboard(
			$this->partnerId,
			EngagementType::tryFrom($this->type) ?? EngagementType::ALL,
			$this->familyId,
		);
	}
}
