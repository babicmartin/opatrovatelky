<?php declare(strict_types=1);

namespace Tests\Performance;

use App\Model\Repository\BabysitterRepository;
use App\Model\Table\OpatrovatelkaTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PerformanceAssertions;

/**
 * Guards the babysitter list query latency under a realistic row count.
 */
final class RepositoryQueryPerformanceTest extends DatabaseTestCase
{
	use PerformanceAssertions;

	public function testBabysitterListQueryStaysFast(): void
	{
		for ($i = 1; $i <= 200; $i++) {
			TestDatabase::createBabysitter([
				OpatrovatelkaTableMap::COL_CLIENT_NUMBER => sprintf('B-%04d', $i),
				OpatrovatelkaTableMap::COL_NAME => 'Name' . $i,
				OpatrovatelkaTableMap::COL_SURNAME => 'Surname' . $i,
			]);
		}

		$repository = $this->getContainer()->getByType(BabysitterRepository::class);

		$this->assertFasterThan('babysitter_list', 150.0, static function () use ($repository): void {
			$pageCount = 0;
			$totalCount = 0;
			$repository->findBabysitterRows(1, 50, null, null, null, null, null, null, null, null, null, $pageCount, $totalCount);
		});
	}
}
