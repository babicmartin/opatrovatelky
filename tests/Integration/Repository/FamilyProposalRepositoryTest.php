<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Form\DTO\Admin\Proposal\ProposalUpdate\ProposalUpdateForm;
use App\Model\Repository\FamilyProposalRepository;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class FamilyProposalRepositoryTest extends DatabaseTestCase
{
	public function testProposalRepositoryListsUpdatesCreatesAndBuildsOptions(): void
	{
		$repository = $this->getContainer()->getByType(FamilyProposalRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_ACRONYM => 'PR',
			UserTableMap::COL_COLOR => '#333333',
			UserTableMap::COL_EMAIL => 'proposal.user@example.test',
		]);
		$partnerId = TestDatabase::createPartner([
			PartnerTableMap::COL_NAME => 'Proposal partner',
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Proposal',
			FamilyTableMap::COL_PARTNER_ID => $partnerId,
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Proposal',
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-PROP',
			OpatrovatelkaTableMap::COL_TYPE => 1,
			OpatrovatelkaTableMap::COL_POHLAVIE => 1,
			OpatrovatelkaTableMap::COL_COUNTRY => 1,
		]);
		$selectedInactiveBabysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Selected',
			OpatrovatelkaTableMap::COL_SURNAME => 'Inactive',
			OpatrovatelkaTableMap::COL_ACTIVE => 0,
		]);
		$proposalId = TestDatabase::insert(FamilyProposalTableMap::TABLE_NAME, [
			FamilyProposalTableMap::COL_FAMILY_ID => $familyId,
			FamilyProposalTableMap::COL_BABYSITTER_ID => $babysitterId,
			FamilyProposalTableMap::COL_STATUS => 1,
			FamilyProposalTableMap::COL_DATE_STARTING_WORK => '2026-08-01',
			FamilyProposalTableMap::COL_DATE_PROPOSAL_SENDED => '2026-07-20',
			FamilyProposalTableMap::COL_NOTICE => 'Proposal notice',
			FamilyProposalTableMap::COL_USER_CREATED => $userId,
			FamilyProposalTableMap::COL_DATE_CREATED => '2026-07-01',
			FamilyProposalTableMap::COL_DELETED => 0,
		]);
		TestDatabase::insert(FamilyProposalTableMap::TABLE_NAME, [
			FamilyProposalTableMap::COL_FAMILY_ID => $familyId,
			FamilyProposalTableMap::COL_DELETED => 1,
		]);

		$pageCount = 0;
		$visibleRows = $repository->findVisibleRows(1, 10, $pageCount);
		$familyRows = $repository->findRowsByFamilyId($familyId);
		$updateRow = $repository->findUpdateRow($proposalId);

		$repository->updateFromForm(new ProposalUpdateForm(
			$proposalId,
			1,
			$selectedInactiveBabysitterId,
			new \DateTimeImmutable('2026-09-01'),
			new \DateTimeImmutable('2026-08-15'),
			'Updated proposal',
		));
		$createdProposalId = $repository->createForFamily($familyId, $userId);
		$createdProposal = $this->getDatabase()->table(FamilyProposalTableMap::TABLE_NAME)->get($createdProposalId);

		self::assertSame(1, $pageCount);
		self::assertCount(1, $visibleRows);
		self::assertSame($proposalId, $visibleRows[0]['id']);
		self::assertSame('Maria Proposal', $visibleRows[0]['familyName']);
		self::assertSame('Anna Proposal', $visibleRows[0]['babysitterName']);
		self::assertSame('PR', $visibleRows[0]['userAcronym']);
		self::assertSame('Proposal partner', $visibleRows[0]['partnerName']);
		self::assertCount(1, $familyRows);
		self::assertSame($proposalId, $familyRows[0]['id']);
		self::assertNotNull($updateRow);
		self::assertSame('Proposal notice', $updateRow['notice']);
		self::assertSame('Nový', $repository->findStatusOptions()[1]);
		self::assertSame('Proposal Anna B-PROP', $repository->findBabysitterOptions()[$babysitterId]);
		self::assertSame('Inactive Selected B-001', $repository->findBabysitterOptions($selectedInactiveBabysitterId)[$selectedInactiveBabysitterId]);

		$updatedProposal = $this->getDatabase()->table(FamilyProposalTableMap::TABLE_NAME)->get($proposalId);
		self::assertNotNull($updatedProposal);
		self::assertSame($selectedInactiveBabysitterId, (int) $updatedProposal->{FamilyProposalTableMap::COL_BABYSITTER_ID});
		self::assertSame('2026-09-01', $updatedProposal->{FamilyProposalTableMap::COL_DATE_STARTING_WORK}->format('Y-m-d'));
		self::assertSame('Updated proposal', $updatedProposal->{FamilyProposalTableMap::COL_NOTICE});
		self::assertNotNull($createdProposal);
		self::assertSame($familyId, (int) $createdProposal->{FamilyProposalTableMap::COL_FAMILY_ID});
		self::assertSame($userId, (int) $createdProposal->{FamilyProposalTableMap::COL_USER_CREATED});
		self::assertSame(date('Y-m-d'), $createdProposal->{FamilyProposalTableMap::COL_DATE_CREATED}->format('Y-m-d'));
	}
}
