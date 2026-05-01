<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\FamilyOffcanvas;

use App\Model\Repository\FamilyRepository;
use Nette\Application\UI\Control;

class FamilyOffcanvasControl extends Control
{
	public function __construct(
		private readonly FamilyRepository $familyRepository,
	) {
	}

	public function render(): void
	{
		$presenter = $this->getPresenter();

		$lastFamilies = array_map(
			fn (array $row): array => $row + [
				'updateUrl' => $presenter->link(':Admin:Family:update', ['id' => $row['id']]),
			],
			$this->familyRepository->findLastForOffcanvas(5),
		);

		$statusItems = array_map(
			fn (array $row): array => $row + [
				'url' => $presenter->link(':Admin:Family:default', ['status' => $row['id']]),
			],
			$this->familyRepository->getStatusCountsForOffcanvas(),
		);

		$countryItems = array_map(
			fn (array $row): array => $row + [
				'url' => $presenter->link(':Admin:Family:default', ['country' => $row['id']]),
			],
			$this->familyRepository->getCountryCountsForOffcanvas(),
		);

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/FamilyOffcanvasControl.latte');
		$template->lastFamilies = $lastFamilies;
		$template->statusItems = $statusItems;
		$template->countryItems = $countryItems;
		$template->render();
	}
}
