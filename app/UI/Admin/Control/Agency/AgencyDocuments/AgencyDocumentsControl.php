<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Agency\AgencyDocuments;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\Control\Document\DocumentsControl;

class AgencyDocumentsControl extends DocumentsControl
{
	public function setContext(int $agencyId): static
	{
		return $this->setOwner('agencies', $agencyId);
	}

	protected function getRequiredResource(): Resource
	{
		return Resource::AGENCY;
	}

	protected function getAuditContext(): string
	{
		return 'documents.agency';
	}

	protected function getFormClass(): string
	{
		return 'agency-document-form';
	}

	protected function getTemplateFile(): string
	{
		return __DIR__ . '/templates/AgencyDocumentsControl.latte';
	}
}
