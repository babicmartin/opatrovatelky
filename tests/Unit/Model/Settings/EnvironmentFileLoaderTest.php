<?php declare(strict_types=1);

namespace Tests\Unit\Model\Settings;

use App\Model\Enum\Settings\Environment;
use App\Model\Settings\EnvironmentFileLoader;
use Nette\Utils\FileSystem;
use RuntimeException;
use Tests\Support\PHPUnit\TestCase;

final class EnvironmentFileLoaderTest extends TestCase
{
	private string $dir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->dir = sys_get_temp_dir() . '/crm-env-loader-' . bin2hex(random_bytes(6));
		FileSystem::createDir($this->dir);
	}

	protected function tearDown(): void
	{
		FileSystem::delete($this->dir);

		parent::tearDown();
	}

	public function testTestEnvironmentNeverReadsTheMachineFile(): void
	{
		$this->write('.env', "DB_PASSWORD=real-secret\nMEDIA_COOKIE_SECRET=real\n");
		$this->write('.env.test', "MEDIA_COOKIE_SECRET=committed\nTEST_DATABASE_USER=root\n");

		self::assertSame(
			['MEDIA_COOKIE_SECRET' => 'committed', 'TEST_DATABASE_USER' => 'root'],
			EnvironmentFileLoader::read($this->dir, Environment::TEST),
		);
	}

	public function testLocalTestFileOverridesTheCommittedOne(): void
	{
		$this->write('.env.test', "TEST_DATABASE_USER=root\nTEST_DATABASE_PASSWORD=\n");
		$this->write('.env.test.local', "TEST_DATABASE_USER=ci\n");

		self::assertSame(
			['TEST_DATABASE_USER' => 'ci', 'TEST_DATABASE_PASSWORD' => ''],
			EnvironmentFileLoader::read($this->dir, Environment::TEST),
		);
	}

	public function testMachineFileOverridesCommittedEnvironmentDefaults(): void
	{
		$this->write('.env.development', "FEATURE_FLAG=off\nDB_NAME=shared\n");
		$this->write('.env.production', "FEATURE_FLAG=production-only\n");
		$this->write('.env', "DB_NAME=mine\nDB_PASSWORD=\"p=1;x\"\n");

		self::assertSame(
			['FEATURE_FLAG' => 'off', 'DB_NAME' => 'mine', 'DB_PASSWORD' => 'p=1;x'],
			EnvironmentFileLoader::read($this->dir, Environment::DEVELOPMENT),
		);
	}

	public function testCommittedEnvironmentDefaultsAreOptional(): void
	{
		$this->write('.env', "DB_NAME=mine\n");

		self::assertSame(['DB_NAME' => 'mine'], EnvironmentFileLoader::read($this->dir, Environment::PRODUCTION));
	}

	public function testMissingMachineFileIsReportedByName(): void
	{
		$this->write('.env.test', "TEST_DATABASE_USER=root\n");

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('config/.env pre prostredie "production"');
		EnvironmentFileLoader::read($this->dir, Environment::PRODUCTION);
	}

	public function testMissingTestFileIsReportedByName(): void
	{
		$this->write('.env', "DB_NAME=mine\n");

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('config/.env.test pre prostredie "test"');
		EnvironmentFileLoader::read($this->dir, Environment::TEST);
	}

	public function testProcessEnvironmentWinsAndOptionalKeysDefaultToEmpty(): void
	{
		$this->write('.env', "DB_NAME=from-file\nDB_USER=file-user\n");
		$process = ['DB_NAME' => 'from-process', 'UNRELATED' => 'ignored'];

		self::assertSame(
			['DB_NAME' => 'from-process', 'DB_USER' => 'file-user', 'MEDIA_COOKIE_SECRET' => ''],
			EnvironmentFileLoader::resolve(
				$this->dir,
				Environment::PRODUCTION,
				['MEDIA_COOKIE_SECRET', 'DB_USER'],
				static fn (string $key): ?string => $process[$key] ?? null,
			),
		);
	}

	public function testCommittedTestFileIsSafeToShare(): void
	{
		$values = EnvironmentFileLoader::read(dirname(__DIR__, 4) . '/config', Environment::TEST);

		foreach (['TEST_DATABASE_DSN', 'TEST_DATABASE_USER', 'TEST_DATABASE_PASSWORD', 'MEDIA_COOKIE_SECRET'] as $key) {
			self::assertArrayHasKey($key, $values);
		}
		self::assertMatchesRegularExpression('~dbname=\w+_test\b~', $values['TEST_DATABASE_DSN']);
		self::assertMatchesRegularExpression('~^[A-Za-z0-9]+$~', $values['MEDIA_COOKIE_SECRET']);
		self::assertStringStartsWith('test', $values['MEDIA_COOKIE_SECRET']);
	}

	private function write(string $name, string $content): void
	{
		FileSystem::write($this->dir . '/' . $name, $content);
	}
}
