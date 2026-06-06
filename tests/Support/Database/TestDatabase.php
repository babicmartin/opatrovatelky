<?php declare(strict_types=1);

namespace Tests\Support\Database;

use App\Model\Table\AgencyTableMap;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use PDO;
use PDOException;
use RuntimeException;

final class TestDatabase
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		$dsn = self::env('TEST_DATABASE_DSN', 'mysql:host=127.0.0.1;port=3307;dbname=opatrovatelky_nette_test');
		$databaseName = self::databaseNameFromDsn($dsn);
		self::assertSafeDatabaseName($databaseName);

		$serverPdo = self::createPdo(self::dsnWithoutDatabase($dsn));
		$serverPdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $databaseName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

		$databasePdo = self::createPdo($dsn);
		self::runSqlFile($databasePdo, dirname(__DIR__, 2) . '/../migrations/database.sql');
		self::runSqlFile($databasePdo, dirname(__DIR__, 2) . '/../migrations/20260530_create_security_login_tables.sql');
		self::runSqlFile($databasePdo, dirname(__DIR__, 2) . '/../migrations/20260530_create_sn_change_log.sql');
		self::runSqlFile($databasePdo, dirname(__DIR__, 2) . '/../report/sql/003_create_babysitter_videos.sql');

		self::$schemaReady = true;
	}

	public static function reset(): void
	{
		self::ensureSchema();

		$pdo = self::pdo();
		$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
		foreach (self::tableNames($pdo) as $tableName) {
			$pdo->exec('TRUNCATE TABLE `' . str_replace('`', '``', $tableName) . '`');
		}
		$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

		self::seedLookupData();
	}

	public static function truncateSecurityTables(): void
	{
		self::reset();
	}

	public static function pdo(): PDO
	{
		self::ensureSchema();

		return self::createPdo(self::env('TEST_DATABASE_DSN', 'mysql:host=127.0.0.1;port=3307;dbname=opatrovatelky_nette_test'));
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function insert(string $table, array $data): int
	{
		$pdo = self::pdo();
		$columns = array_keys($data);
		$columnSql = implode(', ', array_map(static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`', $columns));
		$parameterSql = implode(', ', array_fill(0, count($columns), '?'));

		$statement = $pdo->prepare('INSERT INTO `' . str_replace('`', '``', $table) . "` ($columnSql) VALUES ($parameterSql)");
		$statement->execute(array_values($data));

		return (int) $pdo->lastInsertId();
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createUser(array $overrides = []): int
	{
		return self::insert(UserTableMap::TABLE_NAME, $overrides + [
			UserTableMap::COL_NAME => 'Test',
			UserTableMap::COL_SECOND_NAME => 'User',
			UserTableMap::COL_ACRONYM => 'TU',
			UserTableMap::COL_EMAIL => 'test.user@example.test',
			UserTableMap::COL_PASSWORD => password_hash('secret123!', PASSWORD_DEFAULT),
			UserTableMap::COL_PERMISSION => 10,
			UserTableMap::COL_COLOR => '#8A2062',
			UserTableMap::COL_ACTIVE => 1,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createCountry(array $overrides = []): int
	{
		return self::insert(CountryTableMap::TABLE_NAME, $overrides + [
			CountryTableMap::COL_NAME => 'Slovensko',
			CountryTableMap::COL_COUNTRY => 'SK',
			CountryTableMap::COL_GERMAN => 'Slowakei',
			CountryTableMap::COL_ACTIVE => 1,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createAgency(array $overrides = []): int
	{
		return self::insert(AgencyTableMap::TABLE_NAME, $overrides + [
			AgencyTableMap::COL_NAME => 'Test agency',
			AgencyTableMap::COL_STATE => 1,
			AgencyTableMap::COL_STATUS => 1,
			AgencyTableMap::COL_ACTIVE => 1,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createPartner(array $overrides = []): int
	{
		return self::insert(PartnerTableMap::TABLE_NAME, $overrides + [
			PartnerTableMap::COL_NAME => 'Test partner',
			PartnerTableMap::COL_STATE => 1,
			PartnerTableMap::COL_STATUS => 1,
			PartnerTableMap::COL_ACTIVE => 1,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createBabysitter(array $overrides = []): int
	{
		return self::insert(OpatrovatelkaTableMap::TABLE_NAME, $overrides + [
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-001',
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Testova',
			OpatrovatelkaTableMap::COL_TYPE => 1,
			OpatrovatelkaTableMap::COL_COUNTRY => 1,
			OpatrovatelkaTableMap::COL_STATUS => 1,
			OpatrovatelkaTableMap::COL_ACTIVE => 1,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createFamily(array $overrides = []): int
	{
		return self::insert(FamilyTableMap::TABLE_NAME, $overrides + [
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-001',
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Rodina',
			FamilyTableMap::COL_STATE => 1,
			FamilyTableMap::COL_STATUS => 1,
			FamilyTableMap::COL_TYPE => 1,
			FamilyTableMap::COL_ACTIVE => 1,
			FamilyTableMap::COL_DELETED => 0,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createTurnus(array $overrides = []): int
	{
		return self::insert(TurnusTableMap::TABLE_NAME, $overrides + [
			TurnusTableMap::COL_BABYSITTER_ID => 0,
			TurnusTableMap::COL_FAMILY_ID => 0,
			TurnusTableMap::COL_STATUS => 1,
			TurnusTableMap::COL_DATE_FROM => '2026-06-01',
			TurnusTableMap::COL_DATE_TO => '2026-06-30',
			TurnusTableMap::COL_ACTIVE => 1,
			TurnusTableMap::COL_DELETED => 0,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createFile(array $overrides = []): int
	{
		return self::insert(FileTableMap::TABLE_NAME, $overrides + [
			FileTableMap::COL_DIR => 'babysitters/1',
			FileTableMap::COL_NAME => 'test.pdf',
			FileTableMap::COL_TYPE => 'application/pdf',
			FileTableMap::COL_USER => 1,
			FileTableMap::COL_ACTIVE => 1,
			FileTableMap::COL_STATUS => 1,
		]);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	public static function createChangeLog(array $overrides = []): int
	{
		return self::insert(ChangeLogTableMap::TABLE_NAME, $overrides + [
			ChangeLogTableMap::COL_CONTEXT => 'babysitter.main',
			ChangeLogTableMap::COL_ENTITY_TABLE => OpatrovatelkaTableMap::TABLE_NAME,
			ChangeLogTableMap::COL_ENTITY_ID => 1,
			ChangeLogTableMap::COL_FIELD_NAME => 'notice',
			ChangeLogTableMap::COL_FIELD_LABEL => 'Poznámka',
			ChangeLogTableMap::COL_COLUMN_NAME => 'notice',
			ChangeLogTableMap::COL_VALUE_TYPE => 'text',
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => 'old',
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => 'new',
			ChangeLogTableMap::COL_USER_ID => null,
		]);
	}

	private static function createPdo(string $dsn): PDO
	{
		return new PDO(
			self::ensureCharset($dsn),
			self::env('TEST_DATABASE_USER', 'root'),
			self::env('TEST_DATABASE_PASSWORD', ''),
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			],
		);
	}

	private static function runSqlFile(PDO $pdo, string $file): void
	{
		$sql = file_get_contents($file);
		if ($sql === false) {
			throw new RuntimeException('Unable to read SQL file: ' . $file);
		}

		foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
			$pdo->exec($statement);
		}
	}

	/**
	 * @return list<string>
	 */
	private static function tableNames(PDO $pdo): array
	{
		$statement = $pdo->query('SHOW TABLES');
		if ($statement === false) {
			throw new RuntimeException('Unable to list test database tables.');
		}

		return array_values(array_map(
			static fn(array $row): string => (string) array_values($row)[0],
			$statement->fetchAll(),
		));
	}

	private static function seedLookupData(): void
	{
		$lookupRows = [
			'sn_active' => [
				['id' => 1, 'status' => 'Aktívny'],
				['id' => 2, 'status' => 'Neaktívny'],
			],
			'sn_permission' => [
				['id' => 1, 'permission' => 10, 'name' => 'Admin'],
				['id' => 2, 'permission' => 5, 'name' => 'CEO'],
				['id' => 3, 'permission' => 3, 'name' => 'Dealer'],
				['id' => 4, 'permission' => 2, 'name' => 'Dealer junior'],
			],
			'sn_country' => [
				['id' => 1, 'name' => 'Slovensko', 'country' => 'SK', 'german' => 'Slowakei', 'active' => 1],
				['id' => 2, 'name' => 'Rakúsko', 'country' => 'AT', 'german' => 'Osterreich', 'active' => 1],
			],
			'sn_select_yes_no' => [
				['id' => 1, 'status' => 'Nie'],
				['id' => 2, 'status' => 'Áno'],
			],
			'sn_select_work_role' => [
				['id' => 1, 'slovak' => 'Opatrovateľka'],
				['id' => 2, 'slovak' => 'Pracovník'],
			],
			'sn_select_working_status' => [
				['id' => 1, 'slovak' => 'Aktívny'],
				['id' => 2, 'slovak' => 'Neaktívny'],
			],
			'sn_select_work_position' => [
				['id' => 1, 'position' => 'Opatrovanie'],
				['id' => 2, 'position' => 'Domácnosť'],
			],
			'sn_select_diseases' => [
				['id' => 1, 'slovak' => 'Demencia'],
				['id' => 2, 'slovak' => 'Diabetes'],
			],
			'sn_select_payment_period' => [
				['id' => 1, 'status' => 'Mesačne'],
				['id' => 2, 'status' => 'Týždenne'],
			],
			'sn_select_family_project' => [
				['id' => 1, 'slovak' => 'Rodina'],
				['id' => 2, 'slovak' => 'Projekt'],
			],
			'sn_select_work_status_staff' => [
				['id' => 1, 'contract' => 'Aktívny'],
			],
			'sn_select_smoker' => [
				['id' => 1, 'slovak' => 'Nie'],
				['id' => 2, 'slovak' => 'Áno'],
			],
			'sn_select_accommodation_type' => [
				['id' => 1, 'accommodation_type' => 'Samostatná izba'],
			],
			'sn_select_education' => [
				['id' => 1, 'slovak' => 'Stredoškolské'],
			],
			'sn_select_driving_licence' => [
				['id' => 1, 'slovak' => 'B'],
			],
			'sn_select_language' => [
				['id' => 1, 'slovak' => 'Nemčina'],
			],
			'sn_pohlavie' => [
				['id' => 1, 'pohlavie' => 'Žena'],
				['id' => 2, 'pohlavie' => 'Muž'],
			],
			'sn_status_babysitters' => [
				['id' => 1, 'status' => 'Aktívna', 'color' => '#198754'],
			],
			'sn_status_families' => [
				['id' => 1, 'status' => 'Aktívna', 'color' => '#198754'],
			],
			'sn_status_partners' => [
				['id' => 1, 'status' => 'Aktívny', 'color' => '#198754'],
			],
			'sn_status_turnus' => [
				['id' => 1, 'status' => 'Aktívny', 'color' => '#198754'],
				['id' => 2, 'status' => 'Ukončený', 'color' => '#6c757d'],
			],
			'sn_status_documents' => [
				['id' => 1, 'status' => 'Prijatý', 'color' => '#198754'],
			],
			'sn_status_documents_a1' => [
				['id' => 1, 'status' => 'Vybavené', 'color' => '#198754'],
			],
			'sn_status_fa' => [
				['id' => 1, 'status' => 'Neuhradená', 'color' => '#dc3545'],
			],
			'sn_status_complaint' => [
				['id' => 1, 'status' => 'Bez reklamácie', 'color' => '#6c757d'],
			],
			'sn_status_todo' => [
				['id' => 1, 'status' => 'Otvorená', 'color' => '#ffc107'],
				['id' => 2, 'status' => 'Hotová', 'color' => '#198754'],
			],
			'sn_status_proposal' => [
				['id' => 1, 'status' => 'Nový', 'color' => '#0d6efd'],
			],
			'sany_pages' => [
				['id' => 1, 'name' => 'Homepage', 'url' => 'homepage', 'parent' => 0, 'permission' => 2, 'active' => 1, 'in_menu' => 1],
				['id' => 2, 'name' => 'Nastavenia', 'url' => 'settings', 'parent' => 0, 'permission' => 5, 'active' => 1, 'in_menu' => 1],
				['id' => 3, 'name' => 'Používatelia', 'url' => 'user-management', 'parent' => 0, 'permission' => 10, 'active' => 1, 'in_menu' => 1],
			],
		];

		foreach ($lookupRows as $table => $rows) {
			foreach ($rows as $row) {
				self::insertLookupRow($table, $row);
			}
		}
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function insertLookupRow(string $table, array $row): void
	{
		$columns = self::tableColumns($table);
		$data = array_intersect_key($row, array_flip($columns));

		try {
			self::insert($table, $data);
		} catch (PDOException $e) {
			throw new RuntimeException('Unable to seed lookup table ' . $table . ': ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * @return list<string>
	 */
	private static function tableColumns(string $table): array
	{
		$statement = self::pdo()->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
		if ($statement === false) {
			throw new RuntimeException('Unable to list test database columns for table ' . $table . '.');
		}

		return array_values(array_map(
			static fn(array $row): string => (string) $row['Field'],
			$statement->fetchAll(),
		));
	}

	private static function databaseNameFromDsn(string $dsn): string
	{
		if (preg_match('/(?:^|;)dbname=([^;]+)/', $dsn, $matches) !== 1) {
			throw new RuntimeException('TEST_DATABASE_DSN must contain dbname.');
		}

		return $matches[1];
	}

	private static function assertSafeDatabaseName(string $databaseName): void
	{
		if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName) || !str_ends_with($databaseName, '_test')) {
			throw new RuntimeException('Refusing to run database tests outside a *_test database.');
		}
	}

	private static function dsnWithoutDatabase(string $dsn): string
	{
		return preg_replace('/;?dbname=[^;]+/', '', $dsn) ?? $dsn;
	}

	private static function ensureCharset(string $dsn): string
	{
		return str_contains($dsn, 'charset=') ? $dsn : $dsn . ';charset=utf8mb4';
	}

	private static function env(string $key, string $default): string
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

		return $default;
	}
}
