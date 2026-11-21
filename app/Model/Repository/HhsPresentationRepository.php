<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\DTO\Hhs\HhsPresentationData;
use App\Model\Enum\Turnus\TurnusStatus;
use App\Model\Table\SelectDrivingLicenceTableMap;
use App\Model\Table\SelectSmokerTableMap;
use DateTimeImmutable;
use DateTimeInterface;
use Nette\Database\Explorer;
use Nette\Database\Row;

final readonly class HhsPresentationRepository
{
	public function __construct(
		private BabysitterRepository $babysitters,
		private Explorer $database,
	) {
	}


	public function find(int $id, DateTimeImmutable $generatedAt): ?HhsPresentationData
	{
		$row = $this->babysitters->findUpdateRow($id);
		if ($row === null || $row['type'] !== 1) {
			return null;
		}

		return new HhsPresentationData(
			fullName: trim($row['name'] . ' ' . $row['surname']),
			birthday: $row['birthday']?->format('d.m.Y') ?? '',
			city: $row['city'],
			phone: $row['profilShowContact'] === 1 ? $row['phone'] : '',
			availableFrom: $row['hhs_start_date']?->format('d.m.Y') ?? '',
			assessment: $row['ratingAgency'],
			imageFilename: $row['image'],
			smoker: $this->germanLabel(SelectSmokerTableMap::TABLE_NAME, $row['smoker']),
			drivingLicence: $this->germanLabel(SelectDrivingLicenceTableMap::TABLE_NAME, $row['drivingLicence']),
			nightWork: match ($row['hhs_night_work']) {
				1, '1', true => 'Ja',
				0, '0', false => 'Nein',
				default => '',
			},
			height: (float) $row['height'] > 0 ? $row['height'] . ' cm' : '',
			weight: (float) $row['weight'] > 0 ? $row['weight'] . ' kg' : '',
			positions: $this->findPositions($id, $generatedAt),
			notes: (string) $row['hhs_additional_notes'],
			germanGrade: match ($row['languageSkills']) {
				1, 2 => '2',
				3, 6 => '3',
				5 => '4',
				4 => '5',
				default => '',
			},
			otherLanguages: $row['languageSkillsOther'],
			monthlyCosts: $this->formatEuros($row['hhs_monthly_costs']),
			travelCosts: $this->formatEuros($row['hhs_travel_costs']),
			holidayRate: (string) $row['hhs_holiday_supplement'],
			author: (string) $row['hhs_created_by_name'],
			contactPhone: (string) $row['hhs_contact_phone'],
		);
	}


	private function germanLabel(string $table, int $id): string
	{
		return (string) ($this->database->table($table)->get($id)->german ?? '');
	}


	private function formatEuros(float|int|string|null $amount): string
	{
		if ($amount === null || $amount === '') {
			return '';
		}

		return str_replace(',00 €', ' €', number_format((float) $amount, 2, ',', '') . ' €');
	}


	/** @return list<array{period: string, country: string, city: string}> */
	private function findPositions(int $id, DateTimeImmutable $generatedAt): array
	{
		$rows = $this->database->query(
			'SELECT t.date_from, t.date_to, f.city, c.german AS country
			FROM sn_turnus t
			LEFT JOIN sn_families f ON f.id = t.family_id
			LEFT JOIN sn_country c ON c.id = f.state
			WHERE t.babysitter_id = ? AND t.deleted = 0 AND t.status = ?
				AND t.date_to > ? AND t.date_to <= ?
			ORDER BY t.date_to DESC, t.id DESC LIMIT 2',
			$id,
			TurnusStatus::FINISHED,
			'0001-01-01',
			$generatedAt->format('Y-m-d'),
		)->fetchAll();

		return array_map(static function (Row $row): array {
			$period = [];
			foreach ([$row->date_from, $row->date_to] as $date) {
				if ($date instanceof DateTimeInterface) {
					$period[] = $date->format('d.m.Y');
				}
			}

			return ['period' => implode(' – ', $period), 'country' => (string) $row->country, 'city' => (string) $row->city];
		}, $rows);
	}
}
