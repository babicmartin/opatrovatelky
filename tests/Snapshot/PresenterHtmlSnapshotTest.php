<?php declare(strict_types=1);

namespace Tests\Snapshot;

use App\Model\Enum\UserRole\UserRole;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;
use Tests\Support\PHPUnit\SnapshotAssertions;

/**
 * Snapshots the rendered HTML of a stable admin page. CSRF tokens and asset cache-busting
 * versions are normalized so only structural regressions trip the baseline.
 */
final class PresenterHtmlSnapshotTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;
	use SnapshotAssertions;

	public function testSettingsPageHtmlMatchesBaseline(): void
	{
		$this->loginAs(UserRole::CEO);

		$html = $this->renderPresenter('Admin:Settings');

		$this->assertMatchesSnapshot('admin-settings-page', $this->normalize($html));
	}

	protected function tearDown(): void
	{
		$this->logout();

		parent::tearDown();
	}

	private function normalize(string $html): string
	{
		$html = str_replace("\r\n", "\n", $html);
		// CSRF / antispam tokens
		$html = preg_replace('/(name="_[a-z0-9_]*token[a-z0-9_]*"[^>]*value=")[^"]*(")/i', '$1***$2', $html) ?? $html;
		$html = preg_replace('/(value=")[A-Za-z0-9+\/=_-]{20,}(")/', '$1***$2', $html) ?? $html;
		// asset cache-busting query strings: foo.css?v=123 / ?123456789
		$html = preg_replace('/(\.(?:css|js|png|jpg|jpeg|svg|woff2?))\?[^"\'\s]+/i', '$1?***', $html) ?? $html;

		return trim($html) . "\n";
	}
}
