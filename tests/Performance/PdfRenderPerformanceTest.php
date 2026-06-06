<?php declare(strict_types=1);

namespace Tests\Performance;

use Tests\Support\Fixture\BabysitterPdfFixture;
use Tests\Support\PHPUnit\PerformanceAssertions;
use Tests\Support\PHPUnit\TestCase;

/**
 * Guards the babysitter PDF template render latency (Latte markup layer, not mpdf).
 */
final class PdfRenderPerformanceTest extends TestCase
{
	use PerformanceAssertions;

	public function testTemplateRenderStaysFast(): void
	{
		$data = BabysitterPdfFixture::data();
		$tempDir = dirname(__DIR__, 2) . '/temp/latte-pdf-perf';

		$this->assertFasterThan('pdf_render', 60.0, static function () use ($data, $tempDir): void {
			BabysitterPdfFixture::render($data, $tempDir);
		});
	}
}
