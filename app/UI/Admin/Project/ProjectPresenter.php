<?php declare(strict_types=1);

namespace App\UI\Admin\Project;

use App\Model\Enum\Acl\Resource;
use App\Model\Repository\FamilyRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\FamilyTableMap;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Filter\AlphabetFilter\AlphabetFilterPresenterTrait;
use App\UI\Admin\Control\Project\ProjectList\ProjectListPresenterTrait;

class ProjectPresenter extends AdminPresenter
{
	use AlphabetFilterPresenterTrait;
	use ProjectListPresenterTrait;

	public function __construct(
		private readonly FamilyRepository $familyRepository,
		private readonly ChangeAuditLogger $changeAuditLogger,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::PROJECT->value;
	}

	public function actionDefault(
		?int $page = null,
		?int $status = null,
		?int $country = null,
		?int $partner = null,
		?string $city = null,
		?int $user = null,
	): void
	{
		$firstLetterRaw = $this->getHttpRequest()->getQuery('first-letter');
		$firstLetter = is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null;
		$canManageProjects = $this->getUser()->isLoggedIn()
			&& $this->getUser()->isAllowed(Resource::FAMILY_MANAGEMENT->value);

		$this->template->page = $page;
		$this->template->status = $status;
		$this->template->country = $country;
		$this->template->partner = $partner;
		$this->template->city = $city;
		$this->template->managerUser = $user;
		$this->template->firstLetter = $firstLetter;
		$this->template->canManageProjects = $canManageProjects;
		$this->template->countries = $this->familyRepository->findCountryOptions();
		$this->template->statuses = $this->familyRepository->findStatusOptions();
		$this->template->partners = $this->familyRepository->findPartnerOptions();
		$this->template->cities = $this->familyRepository->findCityOptionsForProjects();
		$this->template->managerCounts = $canManageProjects ? $this->familyRepository->findProjectManagerCounts() : [];
	}

	public function handleCreate(): void
	{
		if (!$this->getUser()->isAllowed(Resource::PROJECT->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->familyRepository->createEmptyProject();
		$this->changeAuditLogger->logCreated('family.shortInfo', FamilyTableMap::TABLE_NAME, $id, 'Projekt', [
			'created_as' => 'project',
		]);
		$this->redirect(':Admin:Family:update', $id);
	}
}
