<?php declare(strict_types = 1);

namespace App\Model\Utils\Checkbox;

class CheckboxService
{
	public function convertToInt(?string $checkboxValue): int
	{
		// Check if the checkbox value is set and equals 'on'
		if (isset($checkboxValue) && $checkboxValue === 'on') {
			return 1;
		}

		return 0;
	}

	public function convertToBool(?string $checkboxValue): bool
	{
		// Check if the checkbox value is set and equals 'on'
		if (isset($checkboxValue) && $checkboxValue === 'on') {
			return true;
		}

		return false;
	}

}