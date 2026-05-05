<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Worker\WorkerList;

interface WorkerListControlFactory
{
	public function create(): WorkerListControl;
}
