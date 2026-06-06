<?php declare(strict_types=1);

namespace Tests\Snapshot;

use Tests\Support\Fixture\BabysitterPdfFixture;
use Tests\Support\PHPUnit\SnapshotAssertions;
use Tests\Support\PHPUnit\TestCase;

/**
 * Renders the babysitter PDF Latte template (the markup layer, not the mpdf binary output)
 * from a fully deterministic fixture and compares it against an approved baseline.
 */
final class PdfTemplateSnapshotTest extends TestCase
{
	use SnapshotAssertions;

	public function testBabysitterPdfTemplateMarkupMatchesBaseline(): void
	{
		$html = BabysitterPdfFixture::render(BabysitterPdfFixture::data(), $this->tempDir());

		$this->assertMatchesSnapshot('babysitter-pdf-template', $this->normalize($html));
	}

	private function normalize(string $html): string
	{
		return str_replace("\r\n", "\n", trim($html)) . "\n";
	}

	private function tempDir(): string
	{
		return dirname(__DIR__, 2) . '/temp/latte-pdf-snapshot';
	}
}
