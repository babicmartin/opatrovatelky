<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\FamilyTableMap;
use App\Model\Table\PartnerTableMap;
use Nette\Database\Row;

class PartnerRepository extends BaseRepository
{
	private const int ACTIVE_FAMILY_STATUS = 1;

	protected function getTableName(): string
	{
		return PartnerTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array{id:int,title:string,count:int}>
	 */
	public function getActiveFamilyCountsForOffcanvas(): array
	{
		$p = PartnerTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$p." . PartnerTableMap::COL_ID . " AS id,
				$p." . PartnerTableMap::COL_NAME . " AS title,
				COUNT($f." . FamilyTableMap::COL_ID . ") AS family_count
			FROM $p
			LEFT JOIN $f ON $f." . FamilyTableMap::COL_PARTNER_ID . " = $p." . PartnerTableMap::COL_ID . "
				AND $f." . FamilyTableMap::COL_STATUS . " = ?
			GROUP BY $p." . PartnerTableMap::COL_ID . ", $p." . PartnerTableMap::COL_NAME . "
			ORDER BY $p." . PartnerTableMap::COL_ID . " DESC
		";

		$rows = $this->database->query($sql, self::ACTIVE_FAMILY_STATUS)->fetchAll();

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->title ?? ''),
				'count' => (int) $row->family_count,
			],
			$rows,
		);
	}
}
