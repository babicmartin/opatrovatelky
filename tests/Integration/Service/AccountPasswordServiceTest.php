<?php declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Security\AccountPasswordService;
use App\Model\Security\AdminAuthenticator;
use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\UserTableMap;
use Nette\Database\Connection;
use Nette\Database\ResultSet;
use Nette\Security\AuthenticationException;
use Nette\Security\SimpleIdentity;
use RuntimeException;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class AccountPasswordServiceTest extends DatabaseTestCase
{
	public function testLegacyPasswordAndPreMigrationSessionAreAcceptedUntilTheFirstChange(): void
	{
		$id = TestDatabase::createUser([UserTableMap::COL_PASSWORD => crypt('previous', '$5$')]);
		$auth = $this->getContainer()->getByType(AdminAuthenticator::class);
		$passwords = $this->getContainer()->getByType(AccountPasswordService::class);
		$oldSession = new SimpleIdentity($id, [UserRole::ADMIN->value]);
		self::assertNotNull($auth->wakeupIdentity($oldSession));
		self::assertSame(1, $passwords->changeOwnPassword($id, 'previous', 'New12345!'));
		self::assertNull($auth->wakeupIdentity($oldSession));
		$current = $auth->authenticate('test.user@example.test', 'New12345!');
		self::assertNotNull($auth->wakeupIdentity($auth->sleepIdentity($current)));
	}


	public function testResetDuringLoginCannotBeOverwrittenByRehash(): void
	{
		$id = TestDatabase::createUser([UserTableMap::COL_PASSWORD => crypt('previous', '$5$')]);
		$adminId = TestDatabase::createUser([UserTableMap::COL_EMAIL => 'admin@example.test']);
		$auth = $this->getContainer()->getByType(AdminAuthenticator::class);
		$passwords = $this->getContainer()->getByType(AccountPasswordService::class);
		$connection = $this->getDatabase()->getConnection();

		// Complete the reset after the login query captured the old credentials and before rehash.
		$connection->onQuery['resetDuringLogin'] = function (Connection $connection, mixed $result) use ($passwords, $id, $adminId): void {
			if ($result instanceof ResultSet && str_contains($result->getQueryString(), UserTableMap::TABLE_NAME)
				&& str_contains($result->getQueryString(), UserTableMap::COL_EMAIL)) {
				unset($connection->onQuery['resetDuringLogin']);
				self::assertTrue($passwords->resetPassword($adminId, $id, UserRole::ADMIN->getPermissionId(), 'Reset123!'));
			}
		};
		try {
			$auth->authenticate('test.user@example.test', 'previous');
			self::fail('A superseded password authenticated during rehash.');
		} catch (AuthenticationException) {
			self::assertSame($id, $auth->authenticate('test.user@example.test', 'Reset123!')->getId());
		} finally {
			unset($connection->onQuery['resetDuringLogin']);
		}
	}


	public function testAuditFailureRollsBackPasswordAndVersion(): void
	{
		$id = TestDatabase::createUser();
		$passwords = $this->getContainer()->getByType(AccountPasswordService::class);
		$connection = $this->getDatabase()->getConnection();
		$failure = new RuntimeException('Simulated audit failure');
		$connection->onQuery['failPasswordAudit'] = static function (Connection $connection, mixed $result) use ($failure): void {
			if ($result instanceof ResultSet && str_starts_with($result->getQueryString(), 'INSERT')
				&& str_contains($result->getQueryString(), SecurityAuditLogTableMap::TABLE_NAME)) {
				throw $failure;
			}
		};
		try {
			$passwords->changeOwnPassword($id, 'secret123!', 'New12345!');
			self::fail('Expected the audit write to fail.');
		} catch (RuntimeException $error) {
			self::assertSame($failure, $error);
		} finally {
			unset($connection->onQuery['failPasswordAudit']);
		}

		$row = $this->getDatabase()->table(UserTableMap::TABLE_NAME)->get($id);
		self::assertNotNull($row);
		self::assertSame(0, $row->{UserTableMap::COL_SESSION_VERSION});
		self::assertTrue($passwords->verify('secret123!', $row->{UserTableMap::COL_PASSWORD}));
		self::assertSame(0, $this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)->count('*'));
	}
}
