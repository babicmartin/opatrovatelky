<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Form\DTO\Admin\Proposal\ProposalUpdate\ProposalUpdateForm;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\StatusProposalTableMap;
use App\Model\Table\UserTableMap;
use Nette\Database\Row;

class FamilyProposalRepository extends BaseRepository
{
	private const int TYPE_BABYSITTER = 1;

	protected function getTableName(): string
	{
		return FamilyProposalTableMap::TABLE_NAME;
	}

	/**
	 * @param int<1, max> $page
	 * @param int<1, max> $itemsPerPage
	 * @return list<array<string, mixed>>
	 */
	public function findVisibleRows(int $page, int $itemsPerPage, ?int &$pageCount = null): array
	{
		$totalCount = (int) $this->findAll()
			->where(FamilyProposalTableMap::COL_DELETED, 0)
			->count('*');
		$pageCount = max(1, (int) ceil($totalCount / $itemsPerPage));
		$page = min(max(1, $page), $pageCount);
		$offset = ($page - 1) * $itemsPerPage;

		$p = FamilyProposalTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$partner = PartnerTableMap::TABLE_NAME;
		$status = StatusProposalTableMap::TABLE_NAME;
		$user = UserTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$p." . FamilyProposalTableMap::COL_ID . " AS id,
				$p." . FamilyProposalTableMap::COL_FAMILY_ID . " AS family_id,
				$p." . FamilyProposalTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$p." . FamilyProposalTableMap::COL_DATE_PROPOSAL_SENDED . " AS date_proposal_sended,
				$p." . FamilyProposalTableMap::COL_DATE_STARTING_WORK . " AS date_starting_work,
				$status." . StatusProposalTableMap::COL_STATUS . " AS proposal_status,
				$status." . StatusProposalTableMap::COL_COLOR . " AS proposal_status_color,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$f." . FamilyTableMap::COL_PARTNER_ID . " AS partner_id,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$user." . UserTableMap::COL_ACRONYM . " AS user_acronym,
				$user." . UserTableMap::COL_COLOR . " AS user_color,
				$partner." . PartnerTableMap::COL_NAME . " AS partner_name
			FROM $p
			LEFT JOIN $status ON $status." . StatusProposalTableMap::COL_ID . " = $p." . FamilyProposalTableMap::COL_STATUS . "
			LEFT JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $p." . FamilyProposalTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $p." . FamilyProposalTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $user ON $user." . UserTableMap::COL_ID . " = $p." . FamilyProposalTableMap::COL_USER_CREATED . "
			LEFT JOIN $partner ON $partner." . PartnerTableMap::COL_ID . " = $f." . FamilyTableMap::COL_PARTNER_ID . "
			WHERE $p." . FamilyProposalTableMap::COL_DELETED . " = 0
			ORDER BY $p." . FamilyProposalTableMap::COL_ID . " DESC
			LIMIT ? OFFSET ?
		";

		return array_map(
			[$this, 'mapListRow'],
			$this->database->query($sql, $itemsPerPage, $offset)->fetchAll(),
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findUpdateRow(int $id): ?array
	{
		$row = $this->findAll()
			->where(FamilyProposalTableMap::COL_ID, $id)
			->where(FamilyProposalTableMap::COL_DELETED, 0)
			->fetch();

		if ($row === null) {
			return null;
		}

		return [
			'id' => (int) $row->{FamilyProposalTableMap::COL_ID},
			'familyId' => (int) ($row->{FamilyProposalTableMap::COL_FAMILY_ID} ?? 0),
			'status' => (int) ($row->{FamilyProposalTableMap::COL_STATUS} ?? 0),
			'babysitterId' => (int) ($row->{FamilyProposalTableMap::COL_BABYSITTER_ID} ?? 0),
			'dateStartingWork' => self::formatDate((string) ($row->{FamilyProposalTableMap::COL_DATE_STARTING_WORK} ?? '')),
			'dateProposalSended' => self::formatDate((string) ($row->{FamilyProposalTableMap::COL_DATE_PROPOSAL_SENDED} ?? '')),
			'notice' => (string) ($row->{FamilyProposalTableMap::COL_NOTICE} ?? ''),
		];
	}

	public function updateFromForm(ProposalUpdateForm $form): void
	{
		$this->update($form->id, [
			FamilyProposalTableMap::COL_STATUS => $form->status,
			FamilyProposalTableMap::COL_BABYSITTER_ID => $form->babysitterId,
			FamilyProposalTableMap::COL_DATE_STARTING_WORK => $this->normalizeDate($form->dateStartingWork),
			FamilyProposalTableMap::COL_DATE_PROPOSAL_SENDED => $this->normalizeDate($form->dateProposalSended),
			FamilyProposalTableMap::COL_NOTICE => $form->notice,
		]);
	}

	public function createForFamily(int $familyId, int $userId): int
	{
		$row = $this->insert([
			FamilyProposalTableMap::COL_FAMILY_ID => $familyId,
			FamilyProposalTableMap::COL_DATE_CREATED => date('Y-m-d'),
			FamilyProposalTableMap::COL_USER_CREATED => $userId,
		]);

		if (!$row instanceof \Nette\Database\Table\ActiveRow) {
			throw new \RuntimeException('Proposal row was not created.');
		}

		return (int) $row->{FamilyProposalTableMap::COL_ID};
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findRowsByFamilyId(int $familyId): array
	{
		$p = FamilyProposalTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$status = StatusProposalTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$p." . FamilyProposalTableMap::COL_ID . " AS id,
				$p." . FamilyProposalTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$p." . FamilyProposalTableMap::COL_DATE_PROPOSAL_SENDED . " AS date_proposal_sended,
				$p." . FamilyProposalTableMap::COL_DATE_STARTING_WORK . " AS date_starting_work,
				$p." . FamilyProposalTableMap::COL_NOTICE . " AS notice,
				$status." . StatusProposalTableMap::COL_STATUS . " AS proposal_status,
				$status." . StatusProposalTableMap::COL_COLOR . " AS proposal_status_color,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname
			FROM $p
			LEFT JOIN $status ON $status." . StatusProposalTableMap::COL_ID . " = $p." . FamilyProposalTableMap::COL_STATUS . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $p." . FamilyProposalTableMap::COL_BABYSITTER_ID . "
			WHERE $p." . FamilyProposalTableMap::COL_FAMILY_ID . " = ?
				AND $p." . FamilyProposalTableMap::COL_DELETED . " = 0
			ORDER BY $p." . FamilyProposalTableMap::COL_ID . " DESC
		";

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'babysitterId' => (int) ($row->babysitter_id ?? 0),
				'status' => (string) ($row->proposal_status ?? ''),
				'statusColor' => (string) ($row->proposal_status_color ?? ''),
				'dateProposalSended' => self::formatDate((string) ($row->date_proposal_sended ?? '')),
				'dateStartingWork' => self::formatDate((string) ($row->date_starting_work ?? '')),
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'notice' => (string) ($row->notice ?? ''),
			],
			$this->database->query($sql, $familyId)->fetchAll(),
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function findStatusOptions(): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table(StatusProposalTableMap::TABLE_NAME)
			->order(StatusProposalTableMap::COL_STATUS . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{StatusProposalTableMap::COL_ID}] = (string) $row->{StatusProposalTableMap::COL_STATUS};
		}

		return $options;
	}

	/**
	 * @return array<int, string>
	 */
	public function findBabysitterOptions(int $selectedId = 0): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table(OpatrovatelkaTableMap::TABLE_NAME)
			->where(OpatrovatelkaTableMap::COL_ACTIVE, 1)
			->where(OpatrovatelkaTableMap::COL_TYPE, self::TYPE_BABYSITTER)
			->where(OpatrovatelkaTableMap::COL_POHLAVIE . ' > ?', 0)
			->where(OpatrovatelkaTableMap::COL_COUNTRY . ' > ?', 0)
			->order(OpatrovatelkaTableMap::COL_SURNAME . ' ASC, ' . OpatrovatelkaTableMap::COL_NAME . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{OpatrovatelkaTableMap::COL_ID}] = $this->formatBabysitterOption($row);
		}

		if ($selectedId > 0 && !isset($options[$selectedId])) {
			$row = $this->database->table(OpatrovatelkaTableMap::TABLE_NAME)->get($selectedId);
			if ($row !== null) {
				$options[$selectedId] = $this->formatBabysitterOption($row);
			}
		}

		return $options;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mapListRow(Row $row): array
	{
		return [
			'id' => (int) $row->id,
			'familyId' => (int) ($row->family_id ?? 0),
			'babysitterId' => (int) ($row->babysitter_id ?? 0),
			'partnerId' => (int) ($row->partner_id ?? 0),
			'status' => (string) ($row->proposal_status ?? ''),
			'statusColor' => (string) ($row->proposal_status_color ?? ''),
			'dateProposalSended' => self::formatDate((string) ($row->date_proposal_sended ?? '')),
			'dateStartingWork' => self::formatDate((string) ($row->date_starting_work ?? '')),
			'familyName' => trim((string) ($row->family_name ?? '') . ' ' . (string) ($row->family_surname ?? '')),
			'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
			'userAcronym' => (string) ($row->user_acronym ?? ''),
			'userColor' => (string) ($row->user_color ?? ''),
			'partnerName' => (string) ($row->partner_name ?? ''),
		];
	}

	private static function formatDate(string $date): string
	{
		if ($date === '' || $date === '0000-00-00' || $date === '-0001-11-30 00:00:00') {
			return '';
		}

		$parts = explode('-', substr($date, 0, 10));
		if (count($parts) !== 3) {
			return '';
		}

		return $parts[2] . '.' . $parts[1] . '.' . $parts[0];
	}

	private function normalizeDate(string $date): ?string
	{
		$date = trim($date);
		if ($date === '') {
			return null;
		}

		$parts = explode('.', $date);
		if (count($parts) !== 3) {
			return null;
		}

		return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
	}

	private function formatBabysitterOption(\Nette\Database\Table\ActiveRow $row): string
	{
		return trim((string) $row->{OpatrovatelkaTableMap::COL_SURNAME}
			. ' '
			. (string) $row->{OpatrovatelkaTableMap::COL_NAME}
			. ' '
			. (string) $row->{OpatrovatelkaTableMap::COL_CLIENT_NUMBER});
	}
}
