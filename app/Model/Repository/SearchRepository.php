<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\AgencyTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\PohlavieTableMap;
use App\Model\Table\StatusBabysitterTableMap;
use App\Model\Table\StatusFamilyTableMap;
use App\Model\Table\StatusPartnerTableMap;
use App\Model\Table\UserTableMap;
use Nette\Database\Explorer;
use Nette\Database\Row;

class SearchRepository
{
	public const int TYPE_BABYSITTER = 1;
	public const int TYPE_FAMILY = 2;
	public const int TYPE_PARTNER = 3;
	public const int TYPE_AGENCY = 4;
	public const int TYPE_FAMILY_CONTACT = 5;

	public function __construct(
		private readonly Explorer $database,
	) {
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function search(int $type, string $term): array
	{
		$term = trim($term);
		if (mb_strlen($term) < 3) {
			return [];
		}

		return match ($type) {
			self::TYPE_BABYSITTER => $this->searchBabysitters($term),
			self::TYPE_FAMILY => $this->searchFamilies($term, false),
			self::TYPE_PARTNER => $this->searchPartners($term),
			self::TYPE_AGENCY => $this->searchAgencies($term),
			self::TYPE_FAMILY_CONTACT => $this->searchFamilies($term, true),
			default => [],
		};
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function searchBabysitters(string $term): array
	{
		$o = OpatrovatelkaTableMap::TABLE_NAME;
		$p = PohlavieTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$a = AgencyTableMap::TABLE_NAME;
		$s = StatusBabysitterTableMap::TABLE_NAME;
		$like = $this->likeTerm($term);

		$sql = "
			SELECT
				$o." . OpatrovatelkaTableMap::COL_ID . " AS id,
				$o." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . " AS client_number,
				$o." . OpatrovatelkaTableMap::COL_NAME . " AS name,
				$o." . OpatrovatelkaTableMap::COL_SURNAME . " AS surname,
				$o." . OpatrovatelkaTableMap::COL_BIRTHDAY . " AS birthday,
				$o." . OpatrovatelkaTableMap::COL_POHLAVIE . " AS gender_id,
				$p." . PohlavieTableMap::COL_POHLAVIE . " AS gender_label,
				$o." . OpatrovatelkaTableMap::COL_COUNTRY . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$o." . OpatrovatelkaTableMap::COL_AGENCY_ID . " AS agency_id,
				$a." . AgencyTableMap::COL_NAME . " AS agency_name,
				$o." . OpatrovatelkaTableMap::COL_STATUS . " AS status_id,
				$s." . StatusBabysitterTableMap::COL_STATUS . " AS status_label,
				$s." . StatusBabysitterTableMap::COL_COLOR . " AS status_color,
				$o." . OpatrovatelkaTableMap::COL_IMAGE . " AS image
			FROM $o
			LEFT JOIN $p ON $p." . PohlavieTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_POHLAVIE . "
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_COUNTRY . "
			LEFT JOIN $a ON $a." . AgencyTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_AGENCY_ID . "
			LEFT JOIN $s ON $s." . StatusBabysitterTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_STATUS . "
			WHERE $o." . OpatrovatelkaTableMap::COL_NAME . " LIKE ?
				OR $o." . OpatrovatelkaTableMap::COL_SURNAME . " LIKE ?
				OR $o." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . " LIKE ?
			ORDER BY $o." . OpatrovatelkaTableMap::COL_NAME . " ASC
		";

		return array_map(
			fn (Row $row): array => [
				'id' => (int) $row->id,
				'clientNumber' => (string) ($row->client_number ?? ''),
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'age' => $this->getAge($row->birthday),
				'genderId' => (int) ($row->gender_id ?? 0),
				'genderLabel' => (string) ($row->gender_label ?? ''),
				'countryId' => (int) ($row->country_id ?? 0),
				'countryImage' => (string) ($row->country_image ?? ''),
				'agencyId' => (int) ($row->agency_id ?? 0),
				'agencyName' => (string) ($row->agency_name ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'image' => (string) ($row->image ?? ''),
			],
			array_values($this->database->query($sql, $like, $like, $like)->fetchAll()),
		);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function searchFamilies(string $term, bool $contactOnly): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$p = PartnerTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;
		$s = StatusFamilyTableMap::TABLE_NAME;
		$like = $this->likeTerm($term);
		$where = $contactOnly
			? "$f." . FamilyTableMap::COL_PERSON_NAME . " LIKE ? OR $f." . FamilyTableMap::COL_PERSON_SURNAME . " LIKE ?"
			: "$f." . FamilyTableMap::COL_SURNAME . " LIKE ? OR $f." . FamilyTableMap::COL_NAME . " LIKE ? OR $f." . FamilyTableMap::COL_CLIENT_NUMBER . " LIKE ?";
		$params = $contactOnly ? [$like, $like] : [$like, $like, $like];

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_CLIENT_NUMBER . " AS client_number,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$f." . FamilyTableMap::COL_STATE . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$f." . FamilyTableMap::COL_PERSON_EMAIL . " AS person_email,
				$f." . FamilyTableMap::COL_PERSON_NAME . " AS person_name,
				$f." . FamilyTableMap::COL_PERSON_SURNAME . " AS person_surname,
				$f." . FamilyTableMap::COL_PARTNER_ID . " AS partner_id,
				$p." . PartnerTableMap::COL_NAME . " AS partner_name,
				$f." . FamilyTableMap::COL_USER_ID . " AS user_id,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym,
				$u." . UserTableMap::COL_COLOR . " AS user_color,
				$f." . FamilyTableMap::COL_STATUS . " AS status_id,
				$s." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$s." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $p ON $p." . PartnerTableMap::COL_ID . " = $f." . FamilyTableMap::COL_PARTNER_ID . "
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $f." . FamilyTableMap::COL_USER_ID . "
			LEFT JOIN $s ON $s." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $where
			ORDER BY $f." . ($contactOnly ? FamilyTableMap::COL_NAME : FamilyTableMap::COL_SURNAME) . " ASC
		";

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'clientNumber' => (string) ($row->client_number ?? ''),
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'countryId' => (int) ($row->country_id ?? 0),
				'countryImage' => (string) ($row->country_image ?? ''),
				'personEmail' => (string) ($row->person_email ?? ''),
				'personName' => (string) ($row->person_name ?? ''),
				'personSurname' => (string) ($row->person_surname ?? ''),
				'partnerId' => (int) ($row->partner_id ?? 0),
				'partnerName' => (string) ($row->partner_name ?? ''),
				'userId' => (int) ($row->user_id ?? 0),
				'userAcronym' => (string) ($row->user_acronym ?? ''),
				'userColor' => (string) ($row->user_color ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			array_values($this->database->query($sql, ...$params)->fetchAll()),
		);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function searchPartners(string $term): array
	{
		return $this->searchCompanies($term, false);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function searchAgencies(string $term): array
	{
		return $this->searchCompanies($term, true);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function searchCompanies(string $term, bool $agency): array
	{
		$like = $this->likeTerm($term);
		$sql = $this->getCompanySearchSql($agency);

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'countryId' => (int) ($row->country_id ?? 0),
				'countryImage' => (string) ($row->country_image ?? ''),
				'web' => (string) ($row->web ?? ''),
				'phone' => (string) ($row->phone ?? ''),
				'email' => (string) ($row->email ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'type' => $agency ? 'agency' : 'partner',
			],
			array_values($this->database->query($sql, $like, $like)->fetchAll()),
		);
	}

	/**
	 * @return literal-string
	 */
	private function getCompanySearchSql(bool $agency): string
	{
		$country = CountryTableMap::TABLE_NAME;
		$status = StatusPartnerTableMap::TABLE_NAME;
		$table = $agency ? AgencyTableMap::TABLE_NAME : PartnerTableMap::TABLE_NAME;

		return "
			SELECT
				$table.id AS id,
				$table.name AS name,
				$table.state AS country_id,
				$country." . CountryTableMap::COL_IMAGE . " AS country_image,
				$table.web AS web,
				$table.phone AS phone,
				$table.email AS email,
				$table.status AS status_id,
				$status." . StatusPartnerTableMap::COL_STATUS . " AS status_label,
				$status." . StatusPartnerTableMap::COL_COLOR . " AS status_color
			FROM $table
			LEFT JOIN $country ON $country." . CountryTableMap::COL_ID . " = $table.state
			LEFT JOIN $status ON $status." . StatusPartnerTableMap::COL_ID . " = $table.status
			WHERE $table.name LIKE ?
				OR $table.person_surname LIKE ?
			ORDER BY $table.name ASC
		";
	}

	private function likeTerm(string $term): string
	{
		return '%' . addcslashes($term, '%_\\') . '%';
	}

	private function getAge(mixed $birthday): string
	{
		if ($birthday === null || (string) $birthday === '') {
			return '-';
		}

		try {
			return (new \DateTimeImmutable((string) $birthday))->diff(new \DateTimeImmutable('today'))->format('%y');
		} catch (\Exception) {
			return '-';
		}
	}
}
