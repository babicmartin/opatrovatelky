<?php declare(strict_types=1);

namespace App\UI\Admin\Control\MissingRegistry\MissingCalendar;

interface MissingCalendarControlFactory
{
	public function create(int $year, int $month): MissingCalendarControl;
}
