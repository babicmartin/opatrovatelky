<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Menu;

use App\Model\Repository\PageRepository;
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

		$template->menuItems = $this->pageRepository->findMenuItems();
		$template->menuDestinations = $this->getMenuDestinations();
		$template->user = $this->user;
		$template->render();
	}

	/**
	 * @return array<string, string>
	 */
	private function getMenuDestinations(): array
	{
		return [
			'homepage' => ':Admin:Home:default',
			'opatrovatelky' => ':Admin:Babysitter:default',
			'families' => ':Admin:Family:default',
			'partneri' => ':Admin:Partner:default',
			'agencies' => ':Admin:Agency:default',
			'turnus' => ':Admin:Turnus:default',
			'projekty' => ':Admin:Project:default',
			'proposal' => ':Admin:Proposal:default',
			'proposal-records' => ':Admin:Proposal:default',
			'stats' => ':Admin:Stats:default',
			'settings' => ':Admin:Settings:default',
			'country' => ':Admin:Country:default',
			'user-management' => ':Admin:UserManagement:default',
			'translation' => ':Admin:Translation:default',
			'missing-registry' => ':Admin:MissingRegistry:default',
			'todo' => ':Admin:Todo:default',
		];
	}
}
