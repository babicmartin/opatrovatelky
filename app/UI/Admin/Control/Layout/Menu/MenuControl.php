<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Menu;

use App\Model\Repository\PageRepository;
use App\Model\Table\PageTableMap;
use Nette\Application\UI\Control;
use Nette\Security\User;

class MenuControl extends Control
{
	public function __construct(
		private readonly PageRepository $pageRepository,
		private readonly User $user,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/MenuControl.latte');

		$template->menuItems = $this->getVisibleItems();
		$template->render();
	}

	/**
	 * Rows of `sany_pages` give the name and the order; the role's permission on the module decides visibility.
	 *
	 * @return list<array{name: string, destination: string}>
	 */
	private function getVisibleItems(): array
	{
		$items = [];
		foreach ($this->pageRepository->findMenuItems() as $row) {
			$page = MenuPage::tryFrom((string) $row->{PageTableMap::COL_URL});
			if ($page === null || !$this->user->isAllowed($page->resource()->value)) {
				continue;
			}

			$items[] = [
				'name' => (string) $row->{PageTableMap::COL_NAME},
				'destination' => $page->destination(),
			];
		}

		return $items;
	}
}
