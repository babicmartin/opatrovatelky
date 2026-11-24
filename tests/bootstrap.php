<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Bratislava');

$sessionDir = dirname(__DIR__) . '/temp/test-sessions';
if (!is_dir($sessionDir)) {
	mkdir($sessionDir, 0777, true);
}
ini_set('session.save_path', $sessionDir);

putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';

// Test values come from config/.env.test (plus the optional, git-ignored config/.env.test.local).
App\Bootstrap::loadEnvironment(App\Model\Enum\Settings\Environment::TEST);
