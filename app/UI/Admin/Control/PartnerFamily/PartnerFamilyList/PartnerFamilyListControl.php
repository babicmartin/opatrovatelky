<?php declare(strict_types=1);

namespace App\UI\Admin\Control\PartnerFamily\PartnerFamilyList;

use App\Model\DTO\Partner\PartnerFamilyOverview;
use App\Model\Enum\Acl\Resource;
use App\Model\Enum\Partner\EngagementType;
use App\Model\Service\Partner\PartnerFamilyEngagementService;
use Nette\Application\UI\Control;
use Nette\Security\User;

/**
 * Families of a partner with total / active / finished babysitter counts.
 */
class PartnerFamilyListControl extends Control
{
	private ?PartnerFamilyOverview $overview = null;


	public function __construct(
		private readonly int $partnerId,
		private readonly PartnerFamilyEngagementService $engagementService,
		private readonly User $user,
	) {
	}


	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/PartnerFamilyListControl.latte');
		$template->partnerId = $this->partnerId;
		$template->overview = $this->getOverview();
		$template->types = [EngagementType::ALL, EngagementType::ACTIVE, EngagementType::FINISHED];
		$template->canOpenFamily = $this->user->isAllowed(Resource::FAMILY->value);
		$template->render();
	}


	private function getOverview(): PartnerFamilyOverview
	{
		return $this->overview ??= $this->engagementService->getFamilyOverview($this->partnerId);
	}
}
