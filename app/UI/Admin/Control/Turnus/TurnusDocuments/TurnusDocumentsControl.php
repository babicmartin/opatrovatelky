<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\TurnusDocuments;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\Control\Document\DocumentsControl;

class TurnusDocumentsControl extends DocumentsControl
{
	public function setContext(int $turnusId): static
	{
		return $this->setOwner('turnus', $turnusId);
	}

	protected function getRequiredResource(): Resource
	{
		return Resource::TURNUS;
	}

	protected function getAuditContext(): string
	{
		return 'documents.turnus';
	}

	protected function getFormClass(): string
	{
		return 'turnus-document-form';
	}

	protected function getTemplateFile(): string
	{
		return __DIR__ . '/templates/TurnusDocumentsControl.latte';
	}

	/** The page of a turnus offers no status of a document, so the form neither carries nor writes one. */
	protected function hasStatus(): bool
	{
		return false;
	}

	protected function getDeleteConfirmMessage(): string
	{
		return 'Naozaj chceš dokument odstrániť?';
	}
}
