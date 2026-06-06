<?php declare(strict_types=1);

namespace Tests\Support\PHPUnit;

use Nette\Utils\FileSystem;

/**
 * Baseline ("snapshot") comparison for stable generated output.
 *
 * First run for a new snapshot writes the baseline and marks the test incomplete,
 * so a missing baseline never passes silently. Set UPDATE_SNAPSHOTS=1 to overwrite
 * existing baselines after an intentional change.
 */
trait SnapshotAssertions
{
	protected function assertMatchesSnapshot(string $name, string $actual): void
	{
		$file = $this->snapshotDir() . '/' . $name . '.snap';

		if (self::shouldUpdateSnapshots() || !is_file($file)) {
			$existed = is_file($file);
			FileSystem::write($file, $actual);

			if (!$existed) {
				self::markTestIncomplete('Snapshot baseline created: ' . $name . '. Re-run to compare.');
			}

			self::addToAssertionCount(1);
			return;
		}

		self::assertSame(FileSystem::read($file), $actual, 'Snapshot mismatch for ' . $name . '. Run with UPDATE_SNAPSHOTS=1 to accept the new output.');
	}

	protected function snapshotDir(): string
	{
		return dirname(__DIR__, 2) . '/Snapshot/__snapshots__';
	}

	private static function shouldUpdateSnapshots(): bool
	{
		foreach ([$_ENV['UPDATE_SNAPSHOTS'] ?? null, $_SERVER['UPDATE_SNAPSHOTS'] ?? null, getenv('UPDATE_SNAPSHOTS')] as $value) {
			if (is_string($value) && $value !== '' && $value !== '0') {
				return true;
			}
		}

		return false;
	}
}
