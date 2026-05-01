<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\HomepageTurnusList;

use App\Model\Repository\TurnusRepository;
use Nette\Application\UI\Control;

class HomepageTurnusListControl extends Control
{
	public function __construct(
		private readonly TurnusRepository $turnusRepository,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/HomepageTurnusListControl.latte');
		$template->upcomingStarts = $this->turnusRepository->findUpcomingStartsForHomepage();
		$template->upcomingEnds = $this->turnusRepository->findUpcomingEndsForHomepage();
		$template->render();
	}
}
