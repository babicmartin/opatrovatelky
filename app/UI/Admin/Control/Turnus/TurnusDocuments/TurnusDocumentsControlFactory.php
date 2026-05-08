<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\TurnusDocuments;

interface TurnusDocumentsControlFactory
{
	public function create(): TurnusDocumentsControl;
}
