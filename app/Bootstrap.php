<?php

declare(strict_types=1);

namespace App;

use App\Model\Enum\Settings\Environment;
use Dotenv\Dotenv;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appDir = dirname(__DIR__);

		$rawEnvironment = self::getEnvironmentValue('APP_ENV') ?? Environment::PRODUCTION->value;
		$environment = Environment::tryFrom($rawEnvironment) ?? Environment::PRODUCTION;
		$debugMode = $environment === Environment::DEVELOPMENT;

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../config/');
        $dotenv->load();

		if ($environment === Environment::TEST) {
			self::setTestEnvironmentDefaults();
		}

		//$configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
        //$configurator->setDebugMode(getenv('APP_DEBUG') === '1');
        $configurator->setDebugMode($debugMode);

        $configurator->enableTracy($appDir . '/log');
		$configurator->setTempDirectory($environment === Environment::TEST ? $appDir . '/temp/test' : $appDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig($appDir . '/config/includes.neon');

        $configurator->addConfig($appDir . '/config/' . $environment->value . '/database.neon');
        $configurator->addConfig($appDir . '/config/' . $environment->value . '/setup.neon');

        $configurator->addDynamicParameters(['env' => $_ENV]);

		return $configurator;
	}

	/**
	 * Keep test configuration usable without local secrets while still allowing
	 * machine-specific overrides through the environment.
	 */
	private static function setTestEnvironmentDefaults(): void
	{
		$defaults = [
			'TEST_DATABASE_DSN' => 'mysql:host=127.0.0.1;port=3307;dbname=opatrovatelky_nette_test',
			'TEST_DATABASE_USER' => 'root',
			'TEST_DATABASE_PASSWORD' => '',
		];

		foreach ($defaults as $key => $default) {
			$value = self::getEnvironmentValue($key) ?? $default;

			$_ENV[$key] = $value;
			$_SERVER[$key] = $value;
		}
	}

	private static function getEnvironmentValue(string $key): ?string
	{
		$values = [
			$_ENV[$key] ?? null,
			$_SERVER[$key] ?? null,
			getenv($key),
		];

		foreach ($values as $value) {
			if (is_string($value) && $value !== '') {
				return $value;
			}
		}

		return null;
	}
}
