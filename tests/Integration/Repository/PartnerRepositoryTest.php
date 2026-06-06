<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Form\DTO\Admin\Partner\PartnerUpdate\PartnerUpdateForm;
use App\Model\Repository\PartnerRepository;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\PartnerTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class PartnerRepositoryTest extends DatabaseTestCase
{
	public function testPartnerRepositoryCreatesUpdatesFiltersAndCountsActiveFamilies(): void
	{
		$repository = $this->getContainer()->getByType(PartnerRepository::class);
		$partnerId = $repository->createEmptyPartner();

		$repository->updateFromForm(new PartnerUpdateForm(
			$partnerId,
			'Agent 5 partner',
			'Partnerstrasse',
			'7',
			'1010',
			'Wien',
			2,
			new \DateTimeImmutable('2026-02-20'),
			'Partnerova',
			'Zuzana',
			'87654321',
			'AT87654321',
			'https://partner.example.test',
			'+431234567',
			'<span>partner@example.test</span>',
			1,
			'Partner notice',
		));
		TestDatabase::createPartner([
			PartnerTableMap::COL_NAME => 'Other partner',
			PartnerTableMap::COL_STATE => 1,
			PartnerTableMap::COL_STATUS => 1,
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_NAME => 'Greta',
			FamilyTableMap::COL_SURNAME => 'Partner',
			FamilyTableMap::COL_STATE => 2,
			FamilyTableMap::COL_PARTNER_ID => $partnerId,
			FamilyTableMap::COL_STATUS => 1,
		]);
		TestDatabase::createFamily([
			FamilyTableMap::COL_SURNAME => 'Inactive status',
			FamilyTableMap::COL_PARTNER_ID => $partnerId,
			FamilyTableMap::COL_STATUS => 2,
		]);

		$updateRow = $repository->findUpdateRow($partnerId);
		$rows = $repository->findPartnerRows(2, 1);
		$families = $repository->findFamiliesForPartner($partnerId);
		$countRows = $repository->getActiveFamilyCountsForOffcanvas();

		self::assertNotNull($updateRow);
		self::assertSame('Agent 5 partner', $updateRow['name']);
		self::assertSame('2026-02-20', $updateRow['dateStart']->format('Y-m-d'));
		self::assertSame('Partner notice', $updateRow['notice']);
		self::assertCount(1, $rows);
		self::assertSame($partnerId, $rows[0]['id']);
		self::assertSame('https://partner.example.test', $rows[0]['websiteUrl']);
		self::assertSame('partner@example.test', $rows[0]['emailLabel']);
		self::assertSame('Rakúsko', $repository->findCountrySelectOptions()[2]);
		self::assertSame('Aktívny', $repository->findStatusSelectOptions()[1]);
		self::assertCount(2, $families);
		self::assertSame($familyId, $families[0]['id']);
		self::assertSame('Partner', $families[0]['surname']);

		$partnerCountRow = array_values(array_filter(
			$countRows,
			static fn (array $row): bool => $row['id'] === $partnerId,
		))[0] ?? null;
		self::assertNotNull($partnerCountRow);
		self::assertSame('Agent 5 partner', $partnerCountRow['title']);
		self::assertSame(1, $partnerCountRow['count']);
	}
}
