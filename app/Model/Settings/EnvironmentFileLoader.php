<?php declare(strict_types=1);

namespace App\Model\Settings;

use App\Model\Enum\Settings\Environment;
use Closure;
use Dotenv\Dotenv;
use RuntimeException;

/**
 * Číta súbory config/.env* podľa prostredia.
 *
 * Priorita od najvyššej:
 *   1. skutočná premenná prostredia (proces, server, CI),
 *   2. súbor stroja mimo Gitu: `.env`, v testoch `.env.test.local`,
 *   3. spoločné hodnoty v Gite: `.env.<prostredie>`.
 *
 * Testy nikdy nečítajú `.env`, takže sa nedostanú k skutočným heslám.
 */
final class EnvironmentFileLoader
{
	/**
	 * Súbory od najnižšej priority po najvyššiu.
	 *
	 * @return list<array{name: string, required: bool}>
	 */
	public static function getFiles(Environment $environment): array
	{
		if ($environment === Environment::TEST) {
			return [
				['name' => '.env.test', 'required' => true],
				['name' => '.env.test.local', 'required' => false],
			];
		}

		return [
			['name' => '.env.' . $environment->value, 'required' => false],
			['name' => '.env', 'required' => true],
		];
	}

	/**
	 * Hodnoty zo súborov bez ohľadu na premenné prostredia.
	 *
	 * @return array<string, string>
	 */
	public static function read(string $configDir, Environment $environment): array
	{
		$values = [];

		foreach (self::getFiles($environment) as $file) {
			$path = rtrim($configDir, '/\\') . '/' . $file['name'];

			if (!is_file($path)) {
				if ($file['required']) {
					throw new RuntimeException(sprintf(
						'Chýba súbor config/%s pre prostredie "%s". Vzor je v config/.env.example.',
						$file['name'],
						$environment->value,
					));
				}

				continue;
			}

			foreach (Dotenv::parse((string) file_get_contents($path)) as $key => $value) {
				$values[$key] = $value ?? '';
			}
		}

		return $values;
	}

	/**
	 * Výsledné hodnoty: súbory, doplnené o voliteľné kľúče a prepísané skutočným prostredím.
	 *
	 * @param list<string> $optionalKeys kľúče, ktoré smú chýbať; dostanú prázdnu hodnotu
	 * @param Closure(string): ?string $processValue neprázdna hodnota zo skutočného prostredia alebo null
	 * @return array<string, string>
	 */
	public static function resolve(
		string $configDir,
		Environment $environment,
		array $optionalKeys,
		Closure $processValue,
	): array
	{
		$values = self::read($configDir, $environment) + array_fill_keys($optionalKeys, '');

		foreach ($values as $key => $value) {
			$values[$key] = $processValue($key) ?? $value;
		}

		return $values;
	}
}
