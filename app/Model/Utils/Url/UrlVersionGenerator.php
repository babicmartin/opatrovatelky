<?php declare(strict_types = 1);

namespace App\Model\Utils\Url;

class UrlVersionGenerator
{
	public function generateNextUrlVersion(string $url): ?string
	{
		if (preg_match('/^(.*-)(\d+)$/', $url, $matches)) {
			// $matches[1] is the base URL, $matches[2] is the number
			$base = $matches[1];
			$number = $matches[2];

			// Increment the number
			$newNumber = intval($number) + 1;

			// Return the new URL with incremented number
			return $base . $newNumber;
		}

		// If no number is present at the end, append '-1'
		return $url . '-1';
	}
}