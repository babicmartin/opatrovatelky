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

$_ENV['TEST_DATABASE_DSN'] ??= 'mysql:host=127.0.0.1;port=3307;dbname=opatrovatelky_nette_test';
$_ENV['TEST_DATABASE_USER'] ??= 'root';
$_ENV['TEST_DATABASE_PASSWORD'] ??= '';

$_SERVER['TEST_DATABASE_DSN'] = $_ENV['TEST_DATABASE_DSN'];
$_SERVER['TEST_DATABASE_USER'] = $_ENV['TEST_DATABASE_USER'];
$_SERVER['TEST_DATABASE_PASSWORD'] = $_ENV['TEST_DATABASE_PASSWORD'];
