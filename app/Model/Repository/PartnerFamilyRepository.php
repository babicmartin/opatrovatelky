<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\SelectWorkPositionTableMap;
use App\Model\Table\StatusFamilyTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use DateTimeImmutable;
use Nette\Database\Row;

/**
 * Families of a partner and their care records (sn_turnus) for the babysitter engagement overview.
 *
 * @phpstan-type FamilyRow array{id:int,name:string,surname:string,countryImage:string,statusLabel:string,statusColor:string}
 * @phpstan-type TurnusRow array{id:int,familyId:int,familyName:string,babysitterId:int,babysitterName:string,statusId:int,statusLabel:string,statusColor:string,dateFrom:?DateTimeImmutable,dateTo:?DateTimeImmutable,workPosition:string}
 */
class PartnerFamilyRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return FamilyTableMap::TABLE_NAME;
	}


	/**
	 * @return list<FamilyRow>
	 */
	public function findFamilies(int $partnerId): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$s = StatusFamilyTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$s." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$s." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $s ON $s." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $f." . FamilyTableMap::COL_PARTNER_ID . " = ?
				AND $f." . FamilyTableMap::COL_DELETED . " = 0
			ORDER BY $f." . FamilyTableMap::COL_SURNAME . " ASC, $f." . FamilyTableMap::COL_NAME . " ASC, $f." . FamilyTableMap::COL_ID . " ASC
		";

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'countryImage' => (string) ($row->country_image ?? ''),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$this->database->query($sql, $partnerId)->fetchAll(),
		);
	}


	/**
	 * @return string|null null when the family does not exist or does not belong to the partner
	 */
	public function findFamilyName(int $partnerId, int $familyId): ?string
	{
		$row = $this->findAll()
			->where(FamilyTableMap::COL_ID, $familyId)
			->where(FamilyTableMap::COL_PARTNER_ID, $partnerId)
			->where(FamilyTableMap::COL_DELETED, 0)
			->fetch();

		if ($row === null) {
			return null;
		}

		return trim((string) ($row->{FamilyTableMap::COL_SURNAME} ?? '') . ' ' . (string) ($row->{FamilyTableMap::COL_NAME} ?? ''));
	}


	/**
	 * Care records with an assigned babysitter in the partner's (non-deleted) families, storno included.
	 *
	 * @return list<TurnusRow>
	 */
	public function findTurnusRows(int $partnerId, ?int $familyId = null): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;
		$o = OpatrovatelkaTableMap::TABLE_NAME;
		$wp = SelectWorkPositionTableMap::TABLE_NAME;

		$params = [$partnerId];
		$familyWhere = '';
		if ($familyId !== null) {
			$familyWhere = "AND $f." . FamilyTableMap::COL_ID . ' = ?';
			$params[] = $familyId;
		}

		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_FAMILY_ID . " AS family_id,
				$t." . TurnusTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$t." . TurnusTableMap::COL_STATUS . " AS status_id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status_label,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$o." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$o." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$wp." . SelectWorkPositionTableMap::COL_POSITION . " AS work_position
			FROM $t
			INNER JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			LEFT JOIN $o ON $o." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $wp ON $wp." . SelectWorkPositionTableMap::COL_ID . " = $t." . TurnusTableMap::COL_WORK_POSITION_ID . "
			WHERE $f." . FamilyTableMap::COL_PARTNER_ID . " = ?
				AND $f." . FamilyTableMap::COL_DELETED . " = 0
				AND $t." . TurnusTableMap::COL_DELETED . " = 0
				AND $t." . TurnusTableMap::COL_BABYSITTER_ID . " > 0
				$familyWhere
			ORDER BY $t." . TurnusTableMap::COL_DATE_FROM . " DESC, $t." . TurnusTableMap::COL_ID . " DESC
		";

		return array_map(
			fn (Row $row): array => [
				'id' => (int) $row->id,
				'familyId' => (int) $row->family_id,
				'familyName' => trim((string) ($row->family_surname ?? '') . ' ' . (string) ($row->family_name ?? '')),
				'babysitterId' => (int) $row->babysitter_id,
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => trim((string) ($row->status_label ?? '')),
				'statusColor' => (string) ($row->status_color ?? ''),
				'dateFrom' => $this->dateService->tryCreateFromDb((string) ($row->date_from ?? '')),
				'dateTo' => $this->dateService->tryCreateFromDb((string) ($row->date_to ?? '')),
				'workPosition' => (string) ($row->work_position ?? ''),
			],
			$this->database->query($sql, ...$params)->fetchAll(),
		);
	}
}
