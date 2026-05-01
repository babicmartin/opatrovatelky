<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Stats\StatsOverview;

use App\Model\Repository\StatsRepository;
use Nette\Application\UI\Control;

class StatsOverviewControl extends Control
{
	public function __construct(
		private readonly StatsRepository $statsRepository,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/StatsOverviewControl.latte');
		$template->sections = $this->withUrls($this->statsRepository->getOverview());
		$template->render();
	}

	/**
	 * @param list<array<string, mixed>> $sections
	 * @return list<array<string, mixed>>
	 */
	private function withUrls(array $sections): array
	{
		foreach ($sections as &$section) {
			foreach (['statusItems', 'countryItems'] as $itemGroup) {
				foreach ($section[$itemGroup] as &$item) {
					$destination = $item['link']['destination'];
					$item['url'] = $destination !== null
						? $this->getPresenter()->link($destination, $item['link']['parameters'])
						: null;
				}
			}
		}

		return $sections;
	}
}
