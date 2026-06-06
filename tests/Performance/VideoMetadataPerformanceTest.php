<?php declare(strict_types=1);

namespace Tests\Performance;

use App\Model\Service\Video\VideoMetadataReader;
use Tests\Support\PHPUnit\PerformanceAssertions;
use Tests\Support\PHPUnit\TestCase;

/**
 * Guards video metadata read latency. Requires a real video file (getID3 needs a video stream),
 * so it self-skips unless a fixture is provided via the TEST_VIDEO_FIXTURE env var or placed at
 * tests/Support/fixtures/sample-video.mp4.
 */
final class VideoMetadataPerformanceTest extends TestCase
{
	use PerformanceAssertions;

	public function testVideoMetadataReadStaysFast(): void
	{
		$path = $this->fixturePath();
		if ($path === null) {
			self::markTestSkipped('No video fixture available (set TEST_VIDEO_FIXTURE or add tests/Support/fixtures/sample-video.mp4).');
		}

		$reader = new VideoMetadataReader();

		$this->assertFasterThan('video_metadata', 250.0, static function () use ($reader, $path): void {
			$reader->read($path);
		});
	}

	private function fixturePath(): ?string
	{
		$env = getenv('TEST_VIDEO_FIXTURE');
		if (is_string($env) && $env !== '' && is_file($env)) {
			return $env;
		}

		$default = dirname(__DIR__) . '/Support/fixtures/sample-video.mp4';

		return is_file($default) ? $default : null;
	}
}
