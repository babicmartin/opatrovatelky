<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\ProjectOffcanvas;

use App\Model\Enum\Acl\Resource;
use App\Model\Repository\FamilyRepository;
use Nette\Application\UI\Control;
use Nette\Security\User;

class ProjectOffcanvasControl extends Control
{
	public function __construct(
		private readonly FamilyRepository $familyRepository,
		private readonly User $user,
	) {
	}

	public function render(): void
	{
		// The panel sits in the layout of every page, so it checks the module permission itself.
		if (!$this->user->isLoggedIn() || !$this->user->isAllowed(Resource::PROJECT->value)) {
			return;
		}

		$presenter = $this->getPresenter();

		$lastProjects = array_map(
			fn (array $row): array => $row + [
				'updateUrl' => $presenter->link(':Admin:Project:update', ['id' => $row['id']]),
				'statusUrl' => $presenter->link(':Admin:Project:default', ['status' => $row['statusId']]),
			],
			$this->familyRepository->findLastProjectsForOffcanvas(5),
		);

		$statusItems = array_map(
			fn (array $row): array => $row + [
				'url' => $presenter->link(':Admin:Project:default', ['status' => $row['id']]),
			],
			$this->familyRepository->getProjectStatusCountsForOffcanvas(),
		);

		$countryItems = array_map(
			fn (array $row): array => $row + [
				'url' => $presenter->link(':Admin:Project:default', ['country' => $row['id']]),
			],
			$this->familyRepository->getProjectCountryCountsForOffcanvas(),
		);

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/ProjectOffcanvasControl.latte');
		$template->lastProjects = $lastProjects;
		$template->statusItems = $statusItems;
		$template->countryItems = $countryItems;
		$template->render();
	}
}
