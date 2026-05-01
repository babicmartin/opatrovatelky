<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\PartnerOffcanvas;

use App\Model\Repository\PartnerRepository;
use Nette\Application\UI\Control;

class PartnerOffcanvasControl extends Control
{
	public function __construct(
		private readonly PartnerRepository $partnerRepository,
	) {
	}

	public function render(): void
	{
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
