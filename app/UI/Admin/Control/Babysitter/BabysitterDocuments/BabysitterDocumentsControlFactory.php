<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Babysitter\BabysitterDocuments;

interface BabysitterDocumentsControlFactory
{
	public function create(): BabysitterDocumentsControl;
}
