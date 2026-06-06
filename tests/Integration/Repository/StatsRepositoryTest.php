<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\StatsRepository;
use App\Model\Table\OpatrovatelkaTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class StatsRepositoryTest extends DatabaseTestCase
{
	public function testGetOverviewCountsByStatusAndCountryAndDropsZeroRows(): void
	{
		TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_STATUS => 1,
			OpatrovatelkaTableMap::COL_COUNTRY => 1,
		]);
		TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-002',
			OpatrovatelkaTableMap::COL_STATUS => 1,
			OpatrovatelkaTableMap::COL_COUNTRY => 2,
		]);
		TestDatabase::createFamily();
		TestDatabase::createPartner();
		TestDatabase::createAgency();

		$overview = $this->getContainer()->getByType(StatsRepository::class)->getOverview();

		self::assertSame(
			['Opatrovateľky', 'Rodiny', 'Partneri', 'Agentúry'],
			array_column($overview, 'title'),
		);

		$babysitters = $overview[0];
		self::assertCount(1, $babysitters['statusItems']);
		self::assertSame('Aktívna', $babysitters['statusItems'][0]['title']);
		self::assertSame('#198754', $babysitters['statusItems'][0]['color']);
		self::assertSame(2, $babysitters['statusItems'][0]['count']);
		self::assertSame(':Admin:Babysitter:default', $babysitters['statusItems'][0]['link']['destination']);
		self::assertSame(['status' => 1], $babysitters['statusItems'][0]['link']['parameters']);

		self::assertSame('Slovensko', $babysitters['countryItems'][0]['title']);
		self::assertSame(1, $babysitters['countryItems'][0]['count']);
		self::assertSame(['country' => 1], $babysitters['countryItems'][0]['link']['parameters']);
		self::assertSame('Rakúsko', $babysitters['countryItems'][1]['title']);
		self::assertSame(1, $babysitters['countryItems'][1]['count']);
		self::assertSame(['country' => 2], $babysitters['countryItems'][1]['link']['parameters']);

		self::assertSame(1, $overview[1]['statusItems'][0]['count']);
		self::assertSame(':Admin:Family:default', $overview[1]['statusItems'][0]['link']['destination']);
		self::assertSame(1, $overview[2]['statusItems'][0]['count']);
		self::assertSame(':Admin:Partner:default', $overview[2]['statusItems'][0]['link']['destination']);
		self::assertSame(1, $overview[3]['statusItems'][0]['count']);
		self::assertSame(':Admin:Agency:default', $overview[3]['statusItems'][0]['link']['destination']);
	}
}
