<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\UnpaidInvoicesWidget;

use App\Model\Repository\TurnusRepository;
use Nette\Application\UI\Control;

class UnpaidInvoicesWidgetControl extends Control
{
	public function __construct(
		private readonly TurnusRepository $turnusRepository,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/UnpaidInvoicesWidgetControl.latte');
		$template->unpaidInvoices = $this->turnusRepository->findUnpaidInvoicesForHomepage();
		$template->render();
	}
}
