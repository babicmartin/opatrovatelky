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

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../config/');
        $dotenv->load();

		$environment = getenv('APP_ENV') === Environment::DEVELOPMENT->value ? Environment::DEVELOPMENT->value : Environment::PRODUCTION->value;
		$debugMode = $environment === Environment::DEVELOPMENT->value;

		//$configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
        //$configurator->setDebugMode(getenv('APP_DEBUG') === '1');
        $configurator->setDebugMode($debugMode);

        $configurator->enableTracy($appDir . '/log');
		$configurator->setTempDirectory($appDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig($appDir . '/config/common.neon');
		$configurator->addConfig($appDir . '/config/extension.neon');
		$configurator->addConfig($appDir . '/config/services.neon');
		$configurator->addConfig($appDir . '/config/console.neon');

        $configurator->addConfig($appDir . '/config/' . $environment . '/database.neon');

        $configurator->addDynamicParameters(['env' => $_ENV]);

		return $configurator;
	}
}
