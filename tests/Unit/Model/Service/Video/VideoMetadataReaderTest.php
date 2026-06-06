<?php declare(strict_types=1);

namespace Tests\Unit\Model\Service\Video;

use App\Model\Service\Video\VideoMetadataReader;
use RuntimeException;
use Tests\Support\PHPUnit\TestCase;

final class VideoMetadataReaderTest extends TestCase
{
	public function testReadReturnsDurationAndMimeForRealVideo(): void
	{
		$metadata = (new VideoMetadataReader())->read($this->fixturePath());

		self::assertSame(1, $metadata['durationSeconds']);
		self::assertSame('video/quicktime', $metadata['mimeType']);
	}

	public function testReadThrowsWhenFileIsMissing(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('prečítať');

		(new VideoMetadataReader())->read(sys_get_temp_dir() . '/missing-' . uniqid() . '.mp4');
	}

	public function testReadThrowsWhenFileHasNoVideoStream(): void
	{
		$path = sys_get_temp_dir() . '/not-a-video-' . uniqid() . '.txt';
		file_put_contents($path, 'plain text, definitely not a video stream');

		try {
			$this->expectException(RuntimeException::class);
			(new VideoMetadataReader())->read($path);
		} finally {
			if (is_file($path)) {
				unlink($path);
			}
		}
	}

	private function fixturePath(): string
	{
		$path = dirname(__DIR__, 4) . '/Support/fixtures/sample-video.mp4';
		self::assertFileExists($path);

		return $path;
	}
}
