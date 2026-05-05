<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Agency\AgencyDocuments;

interface AgencyDocumentsControlFactory
{
	public function create(): AgencyDocumentsControl;
}
