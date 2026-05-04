<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Family\FamilyDocuments;

interface FamilyDocumentsControlFactory
{
	public function create(): FamilyDocumentsControl;
}
