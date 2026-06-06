<?php declare(strict_types=1);

namespace Tests\Support\PHPUnit;

/**
 * Lightweight timing assertions for the Performance suite.
 *
 * Thresholds are deliberately generous so the suite catches order-of-magnitude regressions
 * rather than micro-jitter, and each threshold can be relaxed per-machine via an env var
 * (e.g. PERF_MAX_MS_<KEY>). The median of several iterations is compared, not a single run.
 */
trait PerformanceAssertions
{
	/**
	 * @param callable(): mixed $callback
	 */
	protected function assertFasterThan(string $key, float $defaultMaxMs, callable $callback, int $iterations = 5): void
	{
		// Warm up caches/JIT so the first compile cost is not measured.
		$callback();

		$samples = [];
		for ($i = 0; $i < $iterations; $i++) {
			$start = hrtime(true);
			$callback();
			$samples[] = (hrtime(true) - $start) / 1_000_000.0;
		}

		sort($samples);
		$median = $samples[intdiv(count($samples), 2)];
		$maxMs = $this->thresholdMs($key, $defaultMaxMs);

		self::assertLessThanOrEqual(
			$maxMs,
			$median,
			sprintf('%s median %.2f ms exceeded threshold %.2f ms (samples: %s).', $key, $median, $maxMs, implode(', ', array_map(static fn(float $s): string => sprintf('%.2f', $s), $samples))),
		);
	}

	private function thresholdMs(string $key, float $default): float
	{
		$envKey = 'PERF_MAX_MS_' . strtoupper($key);
		foreach ([$_ENV[$envKey] ?? null, $_SERVER[$envKey] ?? null, getenv($envKey)] as $value) {
			if (is_string($value) && is_numeric($value)) {
				return (float) $value;
			}
		}

		return $default;
	}
}
