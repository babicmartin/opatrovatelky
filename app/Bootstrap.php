<?php

declare(strict_types=1);

namespace App;

use App\Model\Enum\Settings\Environment;
use App\Model\Settings\EnvironmentFileLoader;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
	/**
	 * Kľúče, ktoré v súboroch .env smú chýbať. Kontajner dostane prázdnu hodnotu namiesto pádu,
	 * napríklad keď sa nasadí kód skôr, než sa na hostingu doplní nový kľúč.
	 */
	private const array OptionalEnvironmentKeys = ['MEDIA_COOKIE_SECRET'];

	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appDir = dirname(__DIR__);

		$rawEnvironment = self::getEnvironmentValue('APP_ENV') ?? Environment::PRODUCTION->value;
		$environment = Environment::tryFrom($rawEnvironment) ?? Environment::PRODUCTION;
		$debugMode = $environment === Environment::DEVELOPMENT;

		self::loadEnvironment($environment);

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
	 * Načíta config/.env* pre dané prostredie do $_ENV a $_SERVER.
	 * Ktoré súbory a v akom poradí, určuje EnvironmentFileLoader.
	 */
	public static function loadEnvironment(Environment $environment): void
	{
		$values = EnvironmentFileLoader::resolve(
			dirname(__DIR__) . '/config',
			$environment,
			self::OptionalEnvironmentKeys,
			self::getEnvironmentValue(...),
		);

		foreach ($values as $key => $value) {
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
