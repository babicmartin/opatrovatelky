<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Project\ProjectList;

interface ProjectListControlFactory
{
	public function create(): ProjectListControl;
}
