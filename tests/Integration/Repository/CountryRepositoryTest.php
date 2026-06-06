<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\CountryRepository;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class CountryRepositoryTest extends DatabaseTestCase
{
	public function testCountryRepositoryCreatesUpdatesAndMapsActiveRows(): void
	{
		$repository = $this->getContainer()->getByType(CountryRepository::class);
		$countryId = $repository->createEmpty();

		$repository->updateTextFields($countryId, [
			'name' => 'Cesko',
			'german' => 'Tschechien',
		]);
		$repository->updateImage($countryId, 'cz.png');

		$row = $repository->findRowById($countryId);
		$activeRows = $repository->findActiveRows();

		self::assertNotNull($row);
		self::assertSame('Cesko', $row['name']);
		self::assertSame('Tschechien', $row['german']);
		self::assertSame('cz.png', $row['image']);
		self::assertSame(1, $row['active']);
		self::assertSame($countryId, $activeRows[0]['id']);
		self::assertSame('Cesko', $activeRows[0]['name']);
	}
}
