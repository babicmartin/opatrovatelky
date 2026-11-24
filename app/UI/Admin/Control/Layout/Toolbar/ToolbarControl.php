<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar;

use App\Model\Enum\Acl\Resource;
use Nette\Application\UI\Control;
use Nette\Security\User;

class ToolbarControl extends Control
{
	public function __construct(
		private readonly User $user,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/ToolbarControl.latte');
		// An icon opens a side panel that is rendered only with the same permission.
		$template->canOpenProjectPanel = $this->isAllowed(Resource::PROJECT);
		$template->canOpenFamilyPanel = $this->isAllowed(Resource::FAMILY);
		$template->canOpenPartnerPanel = $this->isAllowed(Resource::PARTNER);
		$template->render();
	}

	private function isAllowed(Resource $resource): bool
	{
		return $this->user->isLoggedIn() && $this->user->isAllowed($resource->value);
	}
}
