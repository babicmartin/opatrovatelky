<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\StatusFaTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use DateTimeImmutable;
use Nette\Database\Row;

class TurnusRepository extends BaseRepository
{
	private const array UNPAID_INVOICE_STATUSES = [0, 1, 2, 4, 6];
	private const array UNPAID_INVOICE_EXCLUDED_TURNUS_STATUSES = [0, 30];
	private const array UNPAID_INVOICE_EXCLUDED_BABYSITTERS = [21, 22, 23, 107, 111, 358];
	private const int GERMANY_COUNTRY_ID = 3;

	protected function getTableName(): string
	{
		return TurnusTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findForMonth(int $year, int $month): array
	{
		$monthDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
		$firstDay = $monthDate->modify('first day of this month')->format('Y-m-d');
		$lastDay = $monthDate->modify('last day of this month')->format('Y-m-d');
		$monthPrefix = $monthDate->format('Y-m');

		$t = TurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_STATUS . " AS status_id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$t." . TurnusTableMap::COL_FAMILY_ID . " AS family_id,
				$t." . TurnusTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$t." . TurnusTableMap::COL_FEE . " AS fee,
				$t." . TurnusTableMap::COL_TRAVEL_COSTS_ARRIVAL . " AS travel_costs_arrival,
				$t." . TurnusTableMap::COL_TRAVEL_COSTS_DEPARTURE . " AS travel_costs_departure,
				$t." . TurnusTableMap::COL_HOLIDAY . " AS holiday,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color
			FROM $t
			INNER JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			WHERE $t." . TurnusTableMap::COL_DELETED . " = 0
				AND $f." . FamilyTableMap::COL_STATE . " = 2
				AND (
					$t." . TurnusTableMap::COL_DATE_FROM . " LIKE ?
					OR $t." . TurnusTableMap::COL_DATE_TO . " LIKE ?
					OR ($t." . TurnusTableMap::COL_DATE_FROM . " < ? AND $t." . TurnusTableMap::COL_DATE_TO . " > ?)
					OR ($t." . TurnusTableMap::COL_DATE_FROM . " < ? AND $t." . TurnusTableMap::COL_DATE_TO . " IS NULL)
					OR ($t." . TurnusTableMap::COL_DATE_FROM . " < ? AND $t." . TurnusTableMap::COL_DATE_TO . " = '0000-00-00')
				)
			ORDER BY $t." . TurnusTableMap::COL_ID . " DESC
		";

		$rows = array_values($this->database->query(
			$sql,
			$monthPrefix . '%',
			$monthPrefix . '%',
			$firstDay,
			$lastDay,
			$firstDay,
			$firstDay,
		)->fetchAll());

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'statusId' => (int) $row->status_id,
				'status' => (string) ($row->status ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'dateFrom' => self::formatDate((string) ($row->date_from ?? '')),
				'dateTo' => self::formatDate((string) ($row->date_to ?? '')),
				'familyId' => (int) $row->family_id,
				'familyName' => trim((string) ($row->family_name ?? '') . ' ' . (string) ($row->family_surname ?? '')),
				'babysitterId' => (int) $row->babysitter_id,
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'fee' => (string) ($row->fee ?? ''),
				'travelCostsArrival' => (string) ($row->travel_costs_arrival ?? ''),
				'travelCostsDeparture' => (string) ($row->travel_costs_departure ?? ''),
				'holiday' => (string) ($row->holiday ?? ''),
			],
			$rows,
		);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findUpcomingStartsForHomepage(int $days = 20): array
	{
		// @phpstan-ignore-next-line argument.type
		$rows = array_values($this->database->query($this->createHomepageTurnusSql(TurnusTableMap::COL_DATE_FROM), $days)->fetchAll());

		return $this->mapHomepageTurnusRows($rows);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findUpcomingEndsForHomepage(int $days = 20): array
	{
		// @phpstan-ignore-next-line argument.type
		$rows = array_values($this->database->query($this->createHomepageTurnusSql(TurnusTableMap::COL_DATE_TO), $days)->fetchAll());

		return $this->mapHomepageTurnusRows($rows);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findUnpaidInvoicesForHomepage(): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$fa = StatusFaTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_PREINVOICE_NUMBER . " AS preinvoice_number,
				$t." . TurnusTableMap::COL_INVOICE_STATUS . " AS invoice_status_id,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$fa." . StatusFaTableMap::COL_STATUS . " AS invoice_status,
				$fa." . StatusFaTableMap::COL_COLOR . " AS invoice_status_color
			FROM $t
			INNER JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $fa ON $fa." . StatusFaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_INVOICE_STATUS . "
			WHERE $t." . TurnusTableMap::COL_INVOICE_STATUS . " <> 3
				AND $t." . TurnusTableMap::COL_INVOICE_STATUS . " <> 5
				AND $t." . TurnusTableMap::COL_WORKING_STATUS . " = 2
				AND $t." . TurnusTableMap::COL_STATUS . " < 30
			ORDER BY $t." . TurnusTableMap::COL_ID . " DESC
		";
		$rows = array_values($this->database->query($sql)->fetchAll());

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'preinvoiceNumber' => (string) ($row->preinvoice_number ?? ''),
				'babysitterName' => self::truncate((string) (($row->babysitter_surname ?? '') ?: ($row->babysitter_name ?? '')), 20),
				'invoiceStatus' => (string) ($row->invoice_status ?? ''),
				'invoiceStatusColor' => (string) ($row->invoice_status_color ?? ''),
			],
			$rows,
		);
	}

	private function createHomepageTurnusSql(string $dateColumn): string
	{
		if (!in_array($dateColumn, [TurnusTableMap::COL_DATE_FROM, TurnusTableMap::COL_DATE_TO], true)) {
			throw new \InvalidArgumentException('Unsupported homepage turnus date column.');
		}

		$t = TurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;

		return "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$t." . TurnusTableMap::COL_FAMILY_ID . " AS family_id,
				$t." . TurnusTableMap::COL_STATUS . " AS status_id,
				$t." . TurnusTableMap::COL_INVOICE_STATUS . " AS invoice_status_id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$f." . FamilyTableMap::COL_STATE . " AS family_state,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color
			FROM $t
			LEFT JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			WHERE $t.$dateColumn < (NOW() + INTERVAL ? DAY)
				AND $t.$dateColumn >= (NOW() - INTERVAL 1 DAY)
				AND $t." . TurnusTableMap::COL_DELETED . " = 0
				AND $t." . TurnusTableMap::COL_STATUS . " < 30
			ORDER BY $t.$dateColumn ASC
		";
	}

	/**
	 * @param list<Row> $rows
	 * @return list<array<string, mixed>>
	 */
	private function mapHomepageTurnusRows(array $rows): array
	{
		return array_map(
			fn (Row $row): array => [
				'id' => (int) $row->id,
				'babysitterId' => (int) $row->babysitter_id,
				'familyId' => (int) $row->family_id,
				'dateFrom' => self::formatDate((string) ($row->date_from ?? '')),
				'dateTo' => self::formatDate((string) ($row->date_to ?? '')),
				'familyName' => trim((string) ($row->family_name ?? '') . ' ' . (string) ($row->family_surname ?? '')),
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'countryImage' => (string) ($row->country_image ?? ''),
				'status' => (string) ($row->status ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'isInvoiceUnpaid' => $this->isInvoiceUnpaid($row),
			],
			$rows,
		);
	}

	private function isInvoiceUnpaid(Row $row): bool
	{
		return !in_array((int) $row->babysitter_id, self::UNPAID_INVOICE_EXCLUDED_BABYSITTERS, true)
			&& in_array((int) $row->invoice_status_id, self::UNPAID_INVOICE_STATUSES, true)
			&& !in_array((int) $row->status_id, self::UNPAID_INVOICE_EXCLUDED_TURNUS_STATUSES, true)
			&& (int) $row->family_state === self::GERMANY_COUNTRY_ID;
	}

	private static function formatDate(string $date): string
	{
		if ($date === '' || $date === '0000-00-00') {
			return '';
		}

		$parts = explode('-', substr($date, 0, 10));
		if (count($parts) !== 3) {
			return '';
		}

		return $parts[2] . '.' . $parts[1] . '.' . $parts[0];
	}

	private static function truncate(string $value, int $length): string
	{
		return strlen($value) > $length ? substr($value, 0, $length) : $value;
	}
}
