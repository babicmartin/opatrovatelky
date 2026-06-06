<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Form\DTO\Admin\Proposal\ProposalUpdate\ProposalUpdateForm;
use App\Model\Form\DTO\Admin\Todo\TodoUpdate\TodoUpdateForm;
use App\Model\Repository\FamilyProposalRepository;
use App\Model\Repository\TodoClientRepository;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\TodoClientTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class TodoProposalRepositoryTest extends DatabaseTestCase
{
	public function testTodoRepositoryFiltersVisibilityMapsRowsAndUpdatesFromDto(): void
	{
		$repository = $this->getContainer()->getByType(TodoClientRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Todo',
			UserTableMap::COL_SECOND_NAME => 'Owner',
			UserTableMap::COL_ACRONYM => 'TO',
			UserTableMap::COL_EMAIL => 'todo.owner@example.test',
			UserTableMap::COL_PERMISSION => 3,
			UserTableMap::COL_COLOR => '#111111',
		]);
		$otherUserId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Todo',
			UserTableMap::COL_SECOND_NAME => 'Other',
			UserTableMap::COL_ACRONYM => 'TT',
			UserTableMap::COL_EMAIL => 'todo.other@example.test',
			UserTableMap::COL_PERMISSION => 3,
			UserTableMap::COL_COLOR => '#222222',
		]);
		$adminUserId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Admin',
			UserTableMap::COL_SECOND_NAME => 'Hidden',
			UserTableMap::COL_EMAIL => 'todo.admin@example.test',
			UserTableMap::COL_PERMISSION => 10,
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Todo',
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-TODO',
		]);
		$inactiveFamilyId = TestDatabase::createFamily([
			FamilyTableMap::COL_SURNAME => 'Inactive',
			FamilyTableMap::COL_ACTIVE => 0,
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Todo',
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-TODO',
		]);
		$inactiveBabysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_SURNAME => 'Inactive',
			OpatrovatelkaTableMap::COL_ACTIVE => 0,
		]);
		$visibleTodoId = TestDatabase::insert(TodoClientTableMap::TABLE_NAME, [
			TodoClientTableMap::COL_FAMILY_ID => $familyId,
			TodoClientTableMap::COL_BABYSITTER_ID => $babysitterId,
			TodoClientTableMap::COL_TODO_FROM_USER => $userId,
			TodoClientTableMap::COL_TODO_TO_USER_1 => $otherUserId,
			TodoClientTableMap::COL_TODO_CREATED => '2026-06-01',
			TodoClientTableMap::COL_TODO_DEADLINE => '2026-06-10',
			TodoClientTableMap::COL_TITLE => 'Visible todo',
			TodoClientTableMap::COL_DESCRIPTION => 'Visible description',
			TodoClientTableMap::COL_ANSWER => 'Visible answer',
			TodoClientTableMap::COL_STATUS => 2,
		]);
		$publicTodoId = TestDatabase::insert(TodoClientTableMap::TABLE_NAME, [
			TodoClientTableMap::COL_TITLE => 'Public todo',
			TodoClientTableMap::COL_STATUS => 2,
		]);
		$hiddenTodoId = TestDatabase::insert(TodoClientTableMap::TABLE_NAME, [
			TodoClientTableMap::COL_TITLE => 'Hidden todo',
			TodoClientTableMap::COL_TODO_FROM_USER => $otherUserId,
			TodoClientTableMap::COL_STATUS => 2,
		]);
		$doneTodoId = TestDatabase::insert(TodoClientTableMap::TABLE_NAME, [
			TodoClientTableMap::COL_TITLE => 'Done todo',
			TodoClientTableMap::COL_TODO_FROM_USER => $userId,
			TodoClientTableMap::COL_STATUS => 1,
		]);

		$activeRows = $repository->findActiveTodoRows($userId, false, null);
		$pageCount = 0;
		$doneRows = $repository->findDoneTodoRows(1, 1, $userId, false, null, $pageCount);
		$updateRow = $repository->findUpdateRowForUser($visibleTodoId, $userId, false);

		$repository->updateFromForm(new TodoUpdateForm(
			$visibleTodoId,
			$inactiveFamilyId,
			$inactiveBabysitterId,
			$otherUserId,
			$userId,
			0,
			new \DateTimeImmutable('2026-07-01'),
			new \DateTimeImmutable('2026-07-15'),
			2,
			'Updated todo',
			'Updated description',
			'Updated answer',
		));
		$createdTodoId = $repository->createEmptyTodo($userId);
		$createdTodo = $this->getDatabase()->table(TodoClientTableMap::TABLE_NAME)->get($createdTodoId);

		$activeIds = array_column($activeRows, 'id');
		self::assertContains($visibleTodoId, $activeIds);
		self::assertContains($publicTodoId, $activeIds);
		self::assertNotContains($hiddenTodoId, $activeIds);
		self::assertNotContains($doneTodoId, $activeIds);
		self::assertSame(1, $pageCount);
		self::assertCount(1, $doneRows);
		self::assertSame($doneTodoId, $doneRows[0]['id']);
		self::assertNotNull($updateRow);
		self::assertSame('Maria Todo F-TODO', $updateRow['familyName']);
		self::assertSame('Anna Todo B-TODO', $updateRow['babysitterName']);
		self::assertNull($repository->getItemForUser($hiddenTodoId, $userId, false));
		self::assertSame('Inactive Anna B-001', $repository->findBabysitterOptions($inactiveBabysitterId)[$inactiveBabysitterId]);
		self::assertSame('Inactive Maria F-001', $repository->findFamilyOptions($inactiveFamilyId)[$inactiveFamilyId]);
		self::assertSame('Admin Hidden', $repository->findUserOptions([$adminUserId])[$adminUserId]);
		self::assertArrayNotHasKey($adminUserId, $repository->findUserOptions());
		self::assertSame('Otvorená', $repository->findStatusOptions()[1]);

		$updatedTodo = $this->getDatabase()->table(TodoClientTableMap::TABLE_NAME)->get($visibleTodoId);
		self::assertNotNull($updatedTodo);
		self::assertSame('Updated todo', $updatedTodo->{TodoClientTableMap::COL_TITLE});
		self::assertSame($inactiveFamilyId, (int) $updatedTodo->{TodoClientTableMap::COL_FAMILY_ID});
		self::assertSame('2026-07-15', $updatedTodo->{TodoClientTableMap::COL_TODO_DEADLINE}->format('Y-m-d'));
		self::assertNotNull($createdTodo);
		self::assertSame($userId, (int) $createdTodo->{TodoClientTableMap::COL_TODO_FROM_USER});
		self::assertSame(date('Y-m-d'), $createdTodo->{TodoClientTableMap::COL_TODO_CREATED}->format('Y-m-d'));
	}

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
