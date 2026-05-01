<?php declare(strict_types = 1);

namespace App\Model\Utils\Validator;

class ImageValidator
{
	public function isImage(string $image): bool
	{
		if (!file_exists($image)) {
			return false;
		}

		$size = @getimagesize($image);
		return $size !== false;
	}
}
