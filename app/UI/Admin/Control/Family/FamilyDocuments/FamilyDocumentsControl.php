<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Family\FamilyDocuments;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\Control\Document\DocumentsControl;

class FamilyDocumentsControl extends DocumentsControl
{
	/**
	 * A family has two document lists; the presenter names the folder and the heading of each.
	 */
	public function setContext(int $familyId, string $dir, string $title): static
	{
		return $this->setOwner($dir, $familyId, $title);
	}

	protected function getRequiredResource(): Resource
	{
		return Resource::FAMILY;
	}

	protected function getAuditContext(): string
	{
		return 'documents.family';
	}

	protected function getFormClass(): string
	{
		return 'family-document-form';
	}

	protected function getTemplateFile(): string
	{
		return __DIR__ . '/templates/FamilyDocumentsControl.latte';
	}
}
