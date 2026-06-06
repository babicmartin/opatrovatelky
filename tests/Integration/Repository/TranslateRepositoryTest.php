<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\TranslateRepository;
use App\Model\Table\TranslateTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class TranslateRepositoryTest extends DatabaseTestCase
{
	public function testFindRowsMapsAndOrdersByIdDescAndUpdateGermanPersists(): void
	{
		$repository = $this->getContainer()->getByType(TranslateRepository::class);
		$firstId = TestDatabase::insert(TranslateTableMap::TABLE_NAME, [
			TranslateTableMap::COL_SLOVAK => 'Dobrý deň',
			TranslateTableMap::COL_GERMAN => 'Guten Tag',
		]);
		$secondId = TestDatabase::insert(TranslateTableMap::TABLE_NAME, [
			TranslateTableMap::COL_SLOVAK => 'Ďakujem',
			TranslateTableMap::COL_GERMAN => '',
		]);

		$rows = $repository->findRows();

		self::assertSame([$secondId, $firstId], array_column($rows, 'id'));
		self::assertSame('Ďakujem', $rows[0]['slovak']);
		self::assertSame('', $rows[0]['german']);
		self::assertSame('Guten Tag', $rows[1]['german']);

		$repository->updateGerman($secondId, ['german' => 'Danke']);

		$updated = $repository->findRows();
		self::assertSame('Danke', $updated[0]['german']);
	}
}
