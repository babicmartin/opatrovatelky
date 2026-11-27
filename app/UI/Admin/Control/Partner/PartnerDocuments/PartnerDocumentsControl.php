<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Partner\PartnerDocuments;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\Control\Document\DocumentsControl;

class PartnerDocumentsControl extends DocumentsControl
{
	public function setContext(int $partnerId): static
	{
		return $this->setOwner('partners', $partnerId);
	}

	protected function getRequiredResource(): Resource
	{
		return Resource::PARTNER;
	}

	protected function getAuditContext(): string
	{
		return 'documents.partner';
	}

	protected function getFormClass(): string
	{
		return 'partner-document-form';
	}

	protected function getTemplateFile(): string
	{
		return __DIR__ . '/templates/PartnerDocumentsControl.latte';
	}
}
