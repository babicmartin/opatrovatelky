<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Validator;

use App\Model\Utils\Validator\ImageValidator;
use Tests\Support\PHPUnit\TestCase;

final class ImageValidatorTest extends TestCase
{
	/** @var list<string> */
	private array $tempFiles = [];

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		$this->tempFiles = [];

		parent::tearDown();
	}

	public function testRecognisesRealImage(): void
	{
		$png = base64_decode(
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMCAQDJ/JZcAAAAAElFTkSuQmCC',
			true,
		);
		self::assertIsString($png);
		$imagePath = $this->writeTempFile('png', $png);

		self::assertTrue((new ImageValidator())->isImage($imagePath));
	}

	public function testRejectsNonImageFileAndMissingPath(): void
	{
		$validator = new ImageValidator();
		$textPath = $this->writeTempFile('txt', 'not an image');

		self::assertFalse($validator->isImage($textPath));
		self::assertFalse($validator->isImage(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.png'));
	}

	private function writeTempFile(string $extension, string $contents): string
	{
		$path = sys_get_temp_dir() . '/image-validator-' . uniqid() . '.' . $extension;
		file_put_contents($path, $contents);
		$this->tempFiles[] = $path;

		return $path;
	}
}
