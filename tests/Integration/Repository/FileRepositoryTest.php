<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\FileRepository;
use App\Model\Table\FileTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class FileRepositoryTest extends DatabaseTestCase
{
	public function testFileRepositoryFindsUpdatesAndSoftDeletesDocuments(): void
	{
		$repository = $this->getContainer()->getByType(FileRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_ACRONYM => 'FD',
			UserTableMap::COL_COLOR => '#778899',
			UserTableMap::COL_EMAIL => 'file.documents@example.test',
		]);
		$documentId = TestDatabase::createFile([
			FileTableMap::COL_DIR => 'families-orders/55',
			FileTableMap::COL_NAME => 'contract.pdf',
			FileTableMap::COL_TYPE => 'application/pdf',
			FileTableMap::COL_USER => $userId,
			FileTableMap::COL_UPLOAD => '2026-06-05 14:30:00',
			FileTableMap::COL_VALID_FROM => '2026-06-01',
			FileTableMap::COL_VALID_TO => '2026-06-30',
			FileTableMap::COL_NOTICE => 'Initial notice',
			FileTableMap::COL_STATUS => 1,
		]);
		TestDatabase::createFile([
			FileTableMap::COL_DIR => 'families-orders/55',
			FileTableMap::COL_NAME => 'inactive.pdf',
			FileTableMap::COL_ACTIVE => 0,
		]);

		$documents = $repository->findDocuments('families-orders', 55);
		$document = $repository->findDocument('families-orders', 55, $documentId);
		$insertedId = $repository->insertDocument('families-orders', 55, 'new.pdf', 'application/pdf', $userId);
		$repository->updateDocument($documentId, [
			'notice' => 'Updated notice',
			'validFrom' => new \DateTimeImmutable('2026-07-01'),
			'validTo' => new \DateTimeImmutable('2026-07-31'),
			'status' => 1,
		]);
		$repository->softDelete($insertedId);

		self::assertCount(1, $documents);
		self::assertSame($documentId, $documents[0]['id']);
		self::assertSame('Nahraté 05.06.2026 o 00:00', $documents[0]['upload']);
		self::assertSame('FD', $documents[0]['userAcronym']);
		self::assertSame('#778899', $documents[0]['userColor']);
		self::assertNotNull($document);
		self::assertSame('Initial notice', $document['notice']);
		self::assertSame('Prijatý', $repository->findStatusOptions()[1]);

		$updatedDocument = $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($documentId);
		$insertedDocument = $this->getDatabase()->table(FileTableMap::TABLE_NAME)->get($insertedId);
		self::assertNotNull($updatedDocument);
		self::assertSame('Updated notice', $updatedDocument->{FileTableMap::COL_NOTICE});
		self::assertSame('2026-07-01', $updatedDocument->{FileTableMap::COL_VALID_FROM}->format('Y-m-d'));
		self::assertNotNull($insertedDocument);
		self::assertSame('families-orders/55', $insertedDocument->{FileTableMap::COL_DIR});
		self::assertSame(0, (int) $insertedDocument->{FileTableMap::COL_ACTIVE});
	}
}
