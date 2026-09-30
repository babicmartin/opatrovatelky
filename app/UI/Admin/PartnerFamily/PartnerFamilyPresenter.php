<?php declare(strict_types=1);

namespace App\UI\Admin\PartnerFamily;

use App\Model\Enum\Acl\Resource;
use App\Model\Enum\Partner\EngagementType;
use App\Model\Repository\PartnerRepository;
use App\Model\Service\Partner\PartnerFamilyEngagementService;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\PartnerFamily\PartnerBabysitterDashboard\PartnerBabysitterDashboardPresenterTrait;
use App\UI\Admin\Control\PartnerFamily\PartnerFamilyList\PartnerFamilyListPresenterTrait;

/**
 * Families of a partner and the babysitters who cared for them (derived from sn_turnus).
 */
class PartnerFamilyPresenter extends AdminPresenter
{
	use PartnerFamilyListPresenterTrait;
	use PartnerBabysitterDashboardPresenterTrait;

	public function __construct(
		private readonly PartnerRepository $partnerRepository,
		private readonly PartnerFamilyEngagementService $engagementService,
	) {
		parent::__construct();
	}


	protected function getResource(): string
	{
		return Resource::PARTNER_FAMILY->value;
	}


	public function actionDefault(int $id): void
	{
		$this->template->partner = $this->loadPartner($id);
	}


	public function actionBabysitters(int $id, string $type = 'all', ?int $family = null): void
	{
		$partner = $this->loadPartner($id);

		$engagementType = EngagementType::tryFrom($type);
		if ($engagementType === null) {
			$this->error('Neplatný typ výpisu.', 404);
		}

		$familyName = null;
		if ($family !== null) {
			$familyName = $this->engagementService->findFamilyName($id, $family);
			if ($familyName === null) {
				$this->error('Rodina nepatrí partnerovi.', 404);
			}
		}

		$this->template->partner = $partner;
		$this->template->type = $engagementType;
		$this->template->familyId = $family;
		$this->template->familyName = $familyName;
		$this->template->canOpenFamily = $this->getUser()->isAllowed(Resource::FAMILY->value);
	}


	/**
	 * @return array<string, mixed>
	 */
	private function loadPartner(int $id): array
	{
		$partner = $this->partnerRepository->findUpdateRow($id);
		if ($partner === null) {
			$this->error('Partner neexistuje.', 404);
		}

		return $partner;
	}
}
