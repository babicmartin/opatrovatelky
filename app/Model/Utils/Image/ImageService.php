<?php declare(strict_types = 1);

namespace App\Model\Utils\Image;

use Nette\Utils\Image;

class ImageService
{
	public function createFromFile(string $file, ?int &$type = null): Image
	{
		return Image::fromFile($file, $type);

	}

	public function resize(Image $image, ?int $width, ?int $height): Image
	{
		$image->resize($width, $height);
		return $image;
	}

	public function save(Image $image, string $file, ?int $quality = null, ?int $type = null): void
	{
		/** @var 1|2|3|6|18|19|null $type */
		$image->save($file, $quality, $type);
	}

	public function detectTypeFromFile(string $file): ?int
	{
		return Image::detectTypeFromFile($file);
	}
}
