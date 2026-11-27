<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Babysitter\BabysitterDocuments;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\Control\Document\DocumentsControl;

class BabysitterDocumentsControl extends DocumentsControl
{
	public function setContext(int $babysitterId): static
	{
		return $this->setOwner('babysitters', $babysitterId);
	}

	protected function getRequiredResource(): Resource
	{
		return Resource::BABYSITTER;
	}

	protected function getAuditContext(): string
	{
		return 'documents.babysitter';
	}

	protected function getFormClass(): string
	{
		return 'babysitter-document-form';
	}

	protected function getTemplateFile(): string
	{
		return __DIR__ . '/templates/BabysitterDocumentsControl.latte';
	}
}
