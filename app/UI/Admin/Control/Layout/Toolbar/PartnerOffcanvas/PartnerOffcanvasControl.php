<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\PartnerOffcanvas;

use App\Model\Enum\Acl\Resource;
use App\Model\Repository\PartnerRepository;
use Nette\Application\UI\Control;
use Nette\Security\User;

class PartnerOffcanvasControl extends Control
{
	public function __construct(
		private readonly PartnerRepository $partnerRepository,
		private readonly User $user,
	) {
	}

	public function render(): void
	{
		// The panel sits in the layout of every page, so it checks the module permission itself.
		if (!$this->user->isLoggedIn() || !$this->user->isAllowed(Resource::PARTNER->value)) {
			return;
		}

		$presenter = $this->getPresenter();

		$partnerItems = array_map(
			fn (array $row): array => $row + [
				'updateUrl' => $presenter->link(':Admin:Partner:update', ['id' => $row['id']]),
			],
			$this->partnerRepository->getActiveFamilyCountsForOffcanvas(),
		);

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/PartnerOffcanvasControl.latte');
		$template->partnerItems = $partnerItems;
		$template->render();
	}
}
