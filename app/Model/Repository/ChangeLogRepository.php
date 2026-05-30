<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\TodoClientTableMap;
use App\Model\Table\TranslateTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use DateTimeImmutable;
use DateTimeInterface;
use Nette\Database\Row;
use Nette\Database\SqlLiteral;
use Nette\Database\Table\ActiveRow;

class ChangeLogRepository extends BaseRepository
{
	/** @var array<string, string> */
	private array $entityLabelCache = [];

	/** @var array<string, array{table:string,id:int,label:string}|null> */
	private array $documentOwnerCache = [];

	/** @var array<string, string> */
	private const array SECTION_OPTIONS = [
		'babysitter' => 'Opatrovateľky / pracovníci',
		'family' => 'Rodiny',
		'turnus' => 'Evidencia / turnusy',
		'proposal' => 'Návrhy',
		'todo' => 'Todo',
		'missingRegistry' => 'Neprítomnosti',
		'partner' => 'Partneri',
		'agency' => 'Agentúry',
		'user' => 'Používatelia',
		'settings' => 'Nastavenia',
	];

	/** @var array<string, list<string>> */
	private const array SECTION_CONTEXT_PATTERNS = [
		'babysitter' => ['babysitter.%', 'documents.babysitter'],
		'family' => ['family.%', 'documents.family'],
		'turnus' => ['turnus.%', 'documents.turnus'],
		'proposal' => ['proposal.%'],
		'todo' => ['todo.%'],
		'missingRegistry' => ['missingRegistry.%'],
		'partner' => ['partner.%', 'documents.partner'],
		'agency' => ['agency.%', 'documents.agency'],
		'user' => ['user.%'],
		'settings' => ['country.%', 'translation.%'],
	];

	/** @var array<string, string> */
	private const array ACTION_OPTIONS = [
		'created' => 'Vytvorené',
		'updated' => 'Upravené',
		'deleted' => 'Vymazané',
	];

	/** @var array<string, string> */
	private const array CONTEXT_LABELS = [
		'babysitter.main' => 'Úvod',
		'babysitter.address' => 'Základné informácie',
		'babysitter.education' => 'Vzdelanie',
		'babysitter.profile' => 'Profil',
		'babysitter.pdf' => 'PDF',
		'babysitter.workProfile' => 'Odborné zameranie',
		'babysitter.video' => 'Video profilu',
		'documents.babysitter' => 'Dokumenty',
		'family.shortInfo' => 'Krátke informácie',
		'family.info' => 'Základné informácie',
		'family.address' => 'Adresa a kontakt',
		'documents.family' => 'Dokumenty',
		'turnus.update' => 'Turnus',
		'turnus.statusA1' => 'Status A1',
		'documents.turnus' => 'Dokumenty',
		'proposal.update' => 'Návrh',
		'todo.update' => 'Úloha',
		'missingRegistry.row' => 'Neprítomnosť',
		'partner.update' => 'Partner',
		'documents.partner' => 'Dokumenty',
		'agency.update' => 'Agentúra',
		'documents.agency' => 'Dokumenty',
		'user.profile' => 'Profil používateľa',
		'user.access' => 'Prístup používateľa',
		'country.update' => 'Krajina',
		'translation.update' => 'Preklad',
	];

	/** @var array<string, string> */
	private const array VALUE_TYPE_LABELS = [
		'bool' => 'Áno/Nie',
		'date' => 'Dátum',
		'file' => 'Súbor',
		'float' => 'Číslo',
		'int' => 'Číslo',
		'junction' => 'Zoznam',
		'select' => 'Výber',
		'text' => 'Text',
	];

	private const string SEARCH_COLLATION = 'utf8mb4_general_ci';

	protected function getTableName(): string
	{
		return ChangeLogTableMap::TABLE_NAME;
	}

	/**
	 * @param array{
	 *     context:string,
	 *     entityTable:string,
	 *     entityId:int,
	 *     fieldName:string,
	 *     fieldLabel:string,
	 *     columnName:?string,
	 *     valueType:string,
	 *     oldValueId:?string,
	 *     oldValueLabel:?string,
	 *     newValueId:?string,
	 *     newValueLabel:?string,
	 *     userId:?int,
	 *     metadata:?array<string, mixed>
	 * } $change
	 */
	public function logChange(array $change): void
	{
		$metadata = $change['metadata'] !== null ? json_encode($change['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
		if ($metadata === false) {
			$metadata = null;
		}

		$this->insert([
			ChangeLogTableMap::COL_CONTEXT => $change['context'],
			ChangeLogTableMap::COL_ENTITY_TABLE => $change['entityTable'],
			ChangeLogTableMap::COL_ENTITY_ID => $change['entityId'],
			ChangeLogTableMap::COL_FIELD_NAME => $change['fieldName'],
			ChangeLogTableMap::COL_FIELD_LABEL => $change['fieldLabel'],
			ChangeLogTableMap::COL_COLUMN_NAME => $change['columnName'],
			ChangeLogTableMap::COL_VALUE_TYPE => $change['valueType'],
			ChangeLogTableMap::COL_OLD_VALUE_ID => $change['oldValueId'],
			ChangeLogTableMap::COL_OLD_VALUE_LABEL => $change['oldValueLabel'],
			ChangeLogTableMap::COL_NEW_VALUE_ID => $change['newValueId'],
			ChangeLogTableMap::COL_NEW_VALUE_LABEL => $change['newValueLabel'],
			ChangeLogTableMap::COL_USER_ID => $change['userId'],
			ChangeLogTableMap::COL_METADATA => $metadata,
		]);
	}

	/**
	 * @param int<1, max> $page
	 * @param int<1, max> $itemsPerPage
	 * @param array<string, mixed> $filters
	 * @return list<array<string, mixed>>
	 */
	public function findRows(int $page, int $itemsPerPage, int &$pageCount, array $filters = []): array
	{
		$c = ChangeLogTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;
		$where = $this->buildFilterWhere($filters, $c, $u);

		$totalCount = (int) $this->database->query("
			SELECT COUNT(*)
			FROM $c
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $c." . ChangeLogTableMap::COL_USER_ID . "
			WHERE ?
		", $where)->fetchField();
		$pageCount = max(1, (int) ceil($totalCount / max(1, $itemsPerPage)));
		$page = min(max(1, $page), $pageCount);
		$offset = ($page - 1) * max(1, $itemsPerPage);

		return array_map([$this, 'mapRow'], $this->database->query("
			SELECT
				$c.*,
				$u." . UserTableMap::COL_NAME . " AS user_name,
				$u." . UserTableMap::COL_SECOND_NAME . " AS user_second_name,
				$u." . UserTableMap::COL_EMAIL . " AS user_email,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym
			FROM $c
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $c." . ChangeLogTableMap::COL_USER_ID . "
			WHERE ?
			ORDER BY $c." . ChangeLogTableMap::COL_ID . " DESC
			LIMIT ? OFFSET ?
		", $where, max(1, $itemsPerPage), $offset)->fetchAll());
	}

	/**
	 * @return array<string, string>
	 */
	public function getSectionOptions(): array
	{
		return self::SECTION_OPTIONS;
	}

	/**
	 * @return array<string, string>
	 */
	public function getActionOptions(): array
	{
		return self::ACTION_OPTIONS;
	}

	/**
	 * @return array<int|string, string>
	 */
	public function findUserOptions(): array
	{
		$c = ChangeLogTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;
		$sql = "
			SELECT DISTINCT
				$u." . UserTableMap::COL_ID . " AS id,
				$u." . UserTableMap::COL_NAME . " AS user_name,
				$u." . UserTableMap::COL_SECOND_NAME . " AS user_second_name,
				$u." . UserTableMap::COL_EMAIL . " AS user_email,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym
			FROM $c
			INNER JOIN $u ON $u." . UserTableMap::COL_ID . " = $c." . ChangeLogTableMap::COL_USER_ID . "
			ORDER BY $u." . UserTableMap::COL_SECOND_NAME . " ASC, $u." . UserTableMap::COL_NAME . " ASC
		";

		$options = [];
		foreach ($this->database->query($sql)->fetchAll() as $row) {
			$name = trim((string) ($row->user_name ?? '') . ' ' . (string) ($row->user_second_name ?? ''));
			if ($name === '') {
				$name = (string) ($row->user_email ?? '');
			}
			if ($name === '') {
				$name = (string) ($row->user_acronym ?? '');
			}
			if ($name === '') {
				$name = 'Používateľ #' . (int) $row->id;
			}

			$options[(string) (int) $row->id] = $name;
		}

		return $options;
	}

	/**
	 * @param array<string, mixed> $filters
	 */
	private function buildFilterWhere(array $filters, string $changeTableAlias, string $userTableAlias): SqlLiteral
	{
		$where = ['1 = 1'];
		$params = [];

		$user = trim((string) ($filters['user'] ?? ''));
		if ($user !== '' && ctype_digit($user)) {
			$where[] = $changeTableAlias . '.' . ChangeLogTableMap::COL_USER_ID . ' = ?';
			$params[] = (int) $user;
		}

		$dateFrom = $this->parseFilterDate((string) ($filters['dateFrom'] ?? ''));
		if ($dateFrom !== null) {
			$where[] = $changeTableAlias . '.' . ChangeLogTableMap::COL_CREATED_AT . ' >= ?';
			$params[] = $dateFrom->format('Y-m-d 00:00:00');
		}

		$dateTo = $this->parseFilterDate((string) ($filters['dateTo'] ?? ''));
		if ($dateTo !== null) {
			$where[] = $changeTableAlias . '.' . ChangeLogTableMap::COL_CREATED_AT . ' <= ?';
			$params[] = $dateTo->format('Y-m-d 23:59:59');
		}

		$section = trim((string) ($filters['section'] ?? ''));
		if (isset(self::SECTION_CONTEXT_PATTERNS[$section])) {
			$sectionWhere = [];
			foreach (self::SECTION_CONTEXT_PATTERNS[$section] as $contextPattern) {
				$sectionWhere[] = $changeTableAlias . '.' . ChangeLogTableMap::COL_CONTEXT . ' LIKE ?';
				$params[] = $contextPattern;
			}
			$where[] = '(' . implode(' OR ', $sectionWhere) . ')';
		}

		$action = trim((string) ($filters['status'] ?? ''));
		if (isset(self::ACTION_OPTIONS[$action])) {
			$where[] = $this->buildActionWhere($action, $changeTableAlias);
		}

		$entityQuery = trim((string) ($filters['entity'] ?? ''));
		if ($entityQuery !== '') {
			$where[] = $this->buildEntitySearchWhere($changeTableAlias, $entityQuery, $params);
		}

		$query = trim((string) ($filters['q'] ?? ''));
		if ($query !== '') {
			$like = '%' . $query . '%';
			$queryConditions = [
				$changeTableAlias . '.' . ChangeLogTableMap::COL_CONTEXT . ' LIKE ?',
				$changeTableAlias . '.' . ChangeLogTableMap::COL_ENTITY_TABLE . ' LIKE ?',
				'CAST(' . $changeTableAlias . '.' . ChangeLogTableMap::COL_ENTITY_ID . ' AS CHAR) LIKE ?',
				$changeTableAlias . '.' . ChangeLogTableMap::COL_FIELD_NAME . ' LIKE ?',
				$changeTableAlias . '.' . ChangeLogTableMap::COL_FIELD_LABEL . ' LIKE ?',
				$changeTableAlias . '.' . ChangeLogTableMap::COL_OLD_VALUE_LABEL . ' LIKE ?',
				$changeTableAlias . '.' . ChangeLogTableMap::COL_NEW_VALUE_LABEL . ' LIKE ?',
				'CAST(' . $changeTableAlias . '.' . ChangeLogTableMap::COL_METADATA . ' AS CHAR) LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_NAME . ' LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_SECOND_NAME . ' LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_EMAIL . ' LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_ACRONYM . ' LIKE ?',
			];
			$where[] = '(' . implode(' OR ', array_map([$this, 'normalizeSearchLikeCondition'], $queryConditions)) . ')';
			for ($i = 0; $i < 12; $i++) {
				$params[] = $like;
			}
		}

		return $this->database::literal(implode(' AND ', $where), ...$params);
	}

	/**
	 * @param list<mixed> $params
	 */
	private function buildEntitySearchWhere(string $changeTableAlias, string $query, array &$params): string
	{
		$like = '%' . $query . '%';
		$conditions = [
			$this->normalizeSearchLikeCondition('CAST(' . $changeTableAlias . '.' . ChangeLogTableMap::COL_ENTITY_ID . ' AS CHAR) LIKE ?'),
		];
		$params[] = $like;

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . OpatrovatelkaTableMap::TABLE_NAME . " entity_babysitter
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_babysitter." . OpatrovatelkaTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND (
						CONCAT_WS(' ', entity_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
						OR CONCAT_WS(' ', entity_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
					)
			)
		", OpatrovatelkaTableMap::TABLE_NAME, $like, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . FamilyTableMap::TABLE_NAME . " entity_family
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_family." . FamilyTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND (
						CONCAT_WS(' ', entity_family." . FamilyTableMap::COL_SURNAME . ", entity_family." . FamilyTableMap::COL_NAME . ", entity_family." . FamilyTableMap::COL_CLIENT_NUMBER . ") LIKE ?
						OR CONCAT_WS(' ', entity_family." . FamilyTableMap::COL_NAME . ", entity_family." . FamilyTableMap::COL_SURNAME . ", entity_family." . FamilyTableMap::COL_CLIENT_NUMBER . ") LIKE ?
					)
			)
		", FamilyTableMap::TABLE_NAME, $like, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . PartnerTableMap::TABLE_NAME . " entity_partner
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_partner." . PartnerTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND entity_partner." . PartnerTableMap::COL_NAME . " LIKE ?
			)
		", PartnerTableMap::TABLE_NAME, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . AgencyTableMap::TABLE_NAME . " entity_agency
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_agency." . AgencyTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND entity_agency." . AgencyTableMap::COL_NAME . " LIKE ?
			)
		", AgencyTableMap::TABLE_NAME, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . CountryTableMap::TABLE_NAME . " entity_country
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_country." . CountryTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND CONCAT_WS(' ', entity_country." . CountryTableMap::COL_NAME . ", entity_country." . CountryTableMap::COL_GERMAN . ") LIKE ?
			)
		", CountryTableMap::TABLE_NAME, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . TranslateTableMap::TABLE_NAME . " entity_translation
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_translation." . TranslateTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND CONCAT_WS(' ', entity_translation." . TranslateTableMap::COL_SLOVAK . ", entity_translation." . TranslateTableMap::COL_GERMAN . ") LIKE ?
			)
		", TranslateTableMap::TABLE_NAME, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . UserTableMap::TABLE_NAME . " entity_user
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_user." . UserTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND (
						CONCAT_WS(' ', entity_user." . UserTableMap::COL_SECOND_NAME . ", entity_user." . UserTableMap::COL_NAME . ", entity_user." . UserTableMap::COL_EMAIL . ", entity_user." . UserTableMap::COL_ACRONYM . ") LIKE ?
						OR CONCAT_WS(' ', entity_user." . UserTableMap::COL_NAME . ", entity_user." . UserTableMap::COL_SECOND_NAME . ", entity_user." . UserTableMap::COL_EMAIL . ", entity_user." . UserTableMap::COL_ACRONYM . ") LIKE ?
					)
			)
		", UserTableMap::TABLE_NAME, $like, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . TurnusTableMap::TABLE_NAME . " entity_turnus
				LEFT JOIN " . FamilyTableMap::TABLE_NAME . " entity_turnus_family ON entity_turnus_family." . FamilyTableMap::COL_ID . " = entity_turnus." . TurnusTableMap::COL_FAMILY_ID . "
				LEFT JOIN " . OpatrovatelkaTableMap::TABLE_NAME . " entity_turnus_babysitter ON entity_turnus_babysitter." . OpatrovatelkaTableMap::COL_ID . " = entity_turnus." . TurnusTableMap::COL_BABYSITTER_ID . "
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_turnus." . TurnusTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND (
						CONCAT_WS(' ', entity_turnus." . TurnusTableMap::COL_ID . ", entity_turnus." . TurnusTableMap::COL_INVOICE_NUMBER . ", entity_turnus." . TurnusTableMap::COL_PREINVOICE_NUMBER . ", entity_turnus_family." . FamilyTableMap::COL_SURNAME . ", entity_turnus_family." . FamilyTableMap::COL_NAME . ", entity_turnus_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_turnus_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_turnus_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_turnus_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
						OR CONCAT_WS(' ', entity_turnus." . TurnusTableMap::COL_ID . ", entity_turnus." . TurnusTableMap::COL_INVOICE_NUMBER . ", entity_turnus." . TurnusTableMap::COL_PREINVOICE_NUMBER . ", entity_turnus_family." . FamilyTableMap::COL_NAME . ", entity_turnus_family." . FamilyTableMap::COL_SURNAME . ", entity_turnus_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_turnus_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_turnus_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_turnus_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
					)
			)
		", TurnusTableMap::TABLE_NAME, $like, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . FamilyProposalTableMap::TABLE_NAME . " entity_proposal
				LEFT JOIN " . FamilyTableMap::TABLE_NAME . " entity_proposal_family ON entity_proposal_family." . FamilyTableMap::COL_ID . " = entity_proposal." . FamilyProposalTableMap::COL_FAMILY_ID . "
				LEFT JOIN " . OpatrovatelkaTableMap::TABLE_NAME . " entity_proposal_babysitter ON entity_proposal_babysitter." . OpatrovatelkaTableMap::COL_ID . " = entity_proposal." . FamilyProposalTableMap::COL_BABYSITTER_ID . "
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_proposal." . FamilyProposalTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND (
						CONCAT_WS(' ', entity_proposal_family." . FamilyTableMap::COL_SURNAME . ", entity_proposal_family." . FamilyTableMap::COL_NAME . ", entity_proposal_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_proposal_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_proposal_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_proposal_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
						OR CONCAT_WS(' ', entity_proposal_family." . FamilyTableMap::COL_NAME . ", entity_proposal_family." . FamilyTableMap::COL_SURNAME . ", entity_proposal_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_proposal_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_proposal_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_proposal_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
					)
			)
		", FamilyProposalTableMap::TABLE_NAME, $like, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . TodoClientTableMap::TABLE_NAME . " entity_todo
				LEFT JOIN " . FamilyTableMap::TABLE_NAME . " entity_todo_family ON entity_todo_family." . FamilyTableMap::COL_ID . " = entity_todo." . TodoClientTableMap::COL_FAMILY_ID . "
				LEFT JOIN " . OpatrovatelkaTableMap::TABLE_NAME . " entity_todo_babysitter ON entity_todo_babysitter." . OpatrovatelkaTableMap::COL_ID . " = entity_todo." . TodoClientTableMap::COL_BABYSITTER_ID . "
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_todo." . TodoClientTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND (
						CONCAT_WS(' ', entity_todo." . TodoClientTableMap::COL_TITLE . ", entity_todo_family." . FamilyTableMap::COL_SURNAME . ", entity_todo_family." . FamilyTableMap::COL_NAME . ", entity_todo_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_todo_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_todo_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_todo_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
						OR CONCAT_WS(' ', entity_todo." . TodoClientTableMap::COL_TITLE . ", entity_todo_family." . FamilyTableMap::COL_NAME . ", entity_todo_family." . FamilyTableMap::COL_SURNAME . ", entity_todo_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_todo_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_todo_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_todo_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
					)
			)
		", TodoClientTableMap::TABLE_NAME, $like, $like);

		$this->addEntitySearchCondition($conditions, $params, "
			EXISTS (
				SELECT 1
				FROM " . FileTableMap::TABLE_NAME . " entity_file
				LEFT JOIN " . OpatrovatelkaTableMap::TABLE_NAME . " entity_file_babysitter ON entity_file." . FileTableMap::COL_DIR . " LIKE 'babysitters/%' AND entity_file_babysitter." . OpatrovatelkaTableMap::COL_ID . " = CAST(SUBSTRING_INDEX(entity_file." . FileTableMap::COL_DIR . ", '/', -1) AS UNSIGNED)
				LEFT JOIN " . FamilyTableMap::TABLE_NAME . " entity_file_family ON (entity_file." . FileTableMap::COL_DIR . " LIKE 'families-orders/%' OR entity_file." . FileTableMap::COL_DIR . " LIKE 'families-contracts/%') AND entity_file_family." . FamilyTableMap::COL_ID . " = CAST(SUBSTRING_INDEX(entity_file." . FileTableMap::COL_DIR . ", '/', -1) AS UNSIGNED)
				LEFT JOIN " . PartnerTableMap::TABLE_NAME . " entity_file_partner ON entity_file." . FileTableMap::COL_DIR . " LIKE 'partners/%' AND entity_file_partner." . PartnerTableMap::COL_ID . " = CAST(SUBSTRING_INDEX(entity_file." . FileTableMap::COL_DIR . ", '/', -1) AS UNSIGNED)
				LEFT JOIN " . AgencyTableMap::TABLE_NAME . " entity_file_agency ON entity_file." . FileTableMap::COL_DIR . " LIKE 'agencies/%' AND entity_file_agency." . AgencyTableMap::COL_ID . " = CAST(SUBSTRING_INDEX(entity_file." . FileTableMap::COL_DIR . ", '/', -1) AS UNSIGNED)
				LEFT JOIN " . TurnusTableMap::TABLE_NAME . " entity_file_turnus ON entity_file." . FileTableMap::COL_DIR . " LIKE 'turnus/%' AND entity_file_turnus." . TurnusTableMap::COL_ID . " = CAST(SUBSTRING_INDEX(entity_file." . FileTableMap::COL_DIR . ", '/', -1) AS UNSIGNED)
				LEFT JOIN " . FamilyTableMap::TABLE_NAME . " entity_file_turnus_family ON entity_file_turnus_family." . FamilyTableMap::COL_ID . " = entity_file_turnus." . TurnusTableMap::COL_FAMILY_ID . "
				LEFT JOIN " . OpatrovatelkaTableMap::TABLE_NAME . " entity_file_turnus_babysitter ON entity_file_turnus_babysitter." . OpatrovatelkaTableMap::COL_ID . " = entity_file_turnus." . TurnusTableMap::COL_BABYSITTER_ID . "
				WHERE $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_TABLE . " = ?
					AND entity_file." . FileTableMap::COL_ID . " = $changeTableAlias." . ChangeLogTableMap::COL_ENTITY_ID . "
					AND (
						CONCAT_WS(' ', entity_file." . FileTableMap::COL_NAME . ", entity_file_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_file_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_file_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ", entity_file_family." . FamilyTableMap::COL_SURNAME . ", entity_file_family." . FamilyTableMap::COL_NAME . ", entity_file_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_file_partner." . PartnerTableMap::COL_NAME . ", entity_file_agency." . AgencyTableMap::COL_NAME . ", entity_file_turnus." . TurnusTableMap::COL_ID . ", entity_file_turnus_family." . FamilyTableMap::COL_SURNAME . ", entity_file_turnus_family." . FamilyTableMap::COL_NAME . ", entity_file_turnus_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_file_turnus_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_file_turnus_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_file_turnus_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
						OR CONCAT_WS(' ', entity_file." . FileTableMap::COL_NAME . ", entity_file_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_file_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_file_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ", entity_file_family." . FamilyTableMap::COL_NAME . ", entity_file_family." . FamilyTableMap::COL_SURNAME . ", entity_file_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_file_partner." . PartnerTableMap::COL_NAME . ", entity_file_agency." . AgencyTableMap::COL_NAME . ", entity_file_turnus." . TurnusTableMap::COL_ID . ", entity_file_turnus_family." . FamilyTableMap::COL_NAME . ", entity_file_turnus_family." . FamilyTableMap::COL_SURNAME . ", entity_file_turnus_family." . FamilyTableMap::COL_CLIENT_NUMBER . ", entity_file_turnus_babysitter." . OpatrovatelkaTableMap::COL_NAME . ", entity_file_turnus_babysitter." . OpatrovatelkaTableMap::COL_SURNAME . ", entity_file_turnus_babysitter." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . ") LIKE ?
					)
			)
		", FileTableMap::TABLE_NAME, $like, $like);

		return '(' . implode(' OR ', $conditions) . ')';
	}

	/**
	 * @param list<string> $conditions
	 * @param list<mixed> $params
	 */
	private function addEntitySearchCondition(array &$conditions, array &$params, string $condition, mixed ...$conditionParams): void
	{
		$conditions[] = $this->normalizeSearchLikeCondition($condition);
		foreach ($conditionParams as $conditionParam) {
			$params[] = $conditionParam;
		}
	}

	private function normalizeSearchLikeCondition(string $condition): string
	{
		return (string) preg_replace_callback(
			'~(?P<expression>CAST\([^?]+?\s+AS\s+CHAR\)|CONCAT_WS\([^?]+?\)|[A-Za-z0-9_]+\.[A-Za-z0-9_]+)\s+LIKE\s+\?~s',
			static fn(array $match): string => 'CONVERT(' . $match['expression'] . ' USING utf8mb4) COLLATE ' . self::SEARCH_COLLATION . ' LIKE CONVERT(? USING utf8mb4) COLLATE ' . self::SEARCH_COLLATION,
			$condition,
		);
	}

	private function buildActionWhere(string $action, string $changeTableAlias): string
	{
		$metadataAction = "JSON_UNQUOTE(JSON_EXTRACT($changeTableAlias." . ChangeLogTableMap::COL_METADATA . ", '$.action'))";
		$hasOldValue = "(($changeTableAlias." . ChangeLogTableMap::COL_OLD_VALUE_ID . " IS NOT NULL AND $changeTableAlias." . ChangeLogTableMap::COL_OLD_VALUE_ID . " <> '') OR ($changeTableAlias." . ChangeLogTableMap::COL_OLD_VALUE_LABEL . " IS NOT NULL AND $changeTableAlias." . ChangeLogTableMap::COL_OLD_VALUE_LABEL . " <> ''))";
		$hasNewValue = "(($changeTableAlias." . ChangeLogTableMap::COL_NEW_VALUE_ID . " IS NOT NULL AND $changeTableAlias." . ChangeLogTableMap::COL_NEW_VALUE_ID . " <> '') OR ($changeTableAlias." . ChangeLogTableMap::COL_NEW_VALUE_LABEL . " IS NOT NULL AND $changeTableAlias." . ChangeLogTableMap::COL_NEW_VALUE_LABEL . " <> ''))";

		return match ($action) {
			'created' => "(($metadataAction IN ('uploaded', 'created', 'added')) OR ($metadataAction IS NULL AND NOT $hasOldValue AND $hasNewValue))",
			'updated' => "(($metadataAction = 'updated') OR (($metadataAction IS NULL OR $metadataAction = '') AND $hasOldValue AND $hasNewValue))",
			'deleted' => "(($metadataAction IN ('deleted', 'removed')) OR ($metadataAction IS NULL AND $hasOldValue AND NOT $hasNewValue))",
			default => '1 = 1',
		};
	}

	private function parseFilterDate(string $value): ?DateTimeImmutable
	{
		$value = trim($value);
		if ($value === '') {
			return null;
		}

		return $this->dateService->tryCreateFromUserInput($value);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mapRow(Row $row): array
	{
		$userName = trim((string) ($row->user_name ?? '') . ' ' . (string) ($row->user_second_name ?? ''));
		if ($userName === '') {
			$userName = (string) ($row->user_email ?? '');
		}
		if ($userName === '') {
			$userName = (string) ($row->user_acronym ?? '');
		}

		return [
			'id' => (int) $row->{ChangeLogTableMap::COL_ID},
			'context' => (string) $row->{ChangeLogTableMap::COL_CONTEXT},
			'entityTable' => (string) $row->{ChangeLogTableMap::COL_ENTITY_TABLE},
			'entityId' => (int) $row->{ChangeLogTableMap::COL_ENTITY_ID},
			'fieldName' => (string) $row->{ChangeLogTableMap::COL_FIELD_NAME},
			'fieldLabel' => (string) $row->{ChangeLogTableMap::COL_FIELD_LABEL},
			'columnName' => (string) ($row->{ChangeLogTableMap::COL_COLUMN_NAME} ?? ''),
			'valueType' => (string) $row->{ChangeLogTableMap::COL_VALUE_TYPE},
			'valueTypeLabel' => $this->resolveValueTypeLabel((string) $row->{ChangeLogTableMap::COL_VALUE_TYPE}),
			'oldValueId' => $row->{ChangeLogTableMap::COL_OLD_VALUE_ID} !== null ? (string) $row->{ChangeLogTableMap::COL_OLD_VALUE_ID} : null,
			'oldValueLabel' => $row->{ChangeLogTableMap::COL_OLD_VALUE_LABEL} !== null ? (string) $row->{ChangeLogTableMap::COL_OLD_VALUE_LABEL} : null,
			'newValueId' => $row->{ChangeLogTableMap::COL_NEW_VALUE_ID} !== null ? (string) $row->{ChangeLogTableMap::COL_NEW_VALUE_ID} : null,
			'newValueLabel' => $row->{ChangeLogTableMap::COL_NEW_VALUE_LABEL} !== null ? (string) $row->{ChangeLogTableMap::COL_NEW_VALUE_LABEL} : null,
			'userId' => $row->{ChangeLogTableMap::COL_USER_ID} !== null ? (int) $row->{ChangeLogTableMap::COL_USER_ID} : null,
			'userName' => $userName,
			'createdAt' => $this->parseDate($row->{ChangeLogTableMap::COL_CREATED_AT}),
			'metadata' => $row->{ChangeLogTableMap::COL_METADATA} !== null ? (string) $row->{ChangeLogTableMap::COL_METADATA} : null,
			'sectionLabel' => $this->resolveSectionLabel((string) $row->{ChangeLogTableMap::COL_CONTEXT}),
			'contextLabel' => $this->resolveContextLabel((string) $row->{ChangeLogTableMap::COL_CONTEXT}),
			'entityLabel' => $this->resolveEntityLabel((string) $row->{ChangeLogTableMap::COL_CONTEXT}, (string) $row->{ChangeLogTableMap::COL_ENTITY_TABLE}, (int) $row->{ChangeLogTableMap::COL_ENTITY_ID}),
			'entityNote' => $this->resolveEntityNote((string) $row->{ChangeLogTableMap::COL_CONTEXT}, (string) $row->{ChangeLogTableMap::COL_ENTITY_TABLE}, (int) $row->{ChangeLogTableMap::COL_ENTITY_ID}),
			'entityLinkType' => $this->resolveEntityLinkType((string) $row->{ChangeLogTableMap::COL_CONTEXT}, (string) $row->{ChangeLogTableMap::COL_ENTITY_TABLE}, (int) $row->{ChangeLogTableMap::COL_ENTITY_ID}),
			'entityLinkId' => $this->resolveEntityLinkId((string) $row->{ChangeLogTableMap::COL_CONTEXT}, (string) $row->{ChangeLogTableMap::COL_ENTITY_TABLE}, (int) $row->{ChangeLogTableMap::COL_ENTITY_ID}),
			'actionLabel' => $this->resolveActionLabel(
				$row->{ChangeLogTableMap::COL_METADATA} !== null ? (string) $row->{ChangeLogTableMap::COL_METADATA} : null,
				$row->{ChangeLogTableMap::COL_OLD_VALUE_ID} !== null ? (string) $row->{ChangeLogTableMap::COL_OLD_VALUE_ID} : null,
				$row->{ChangeLogTableMap::COL_OLD_VALUE_LABEL} !== null ? (string) $row->{ChangeLogTableMap::COL_OLD_VALUE_LABEL} : null,
				$row->{ChangeLogTableMap::COL_NEW_VALUE_ID} !== null ? (string) $row->{ChangeLogTableMap::COL_NEW_VALUE_ID} : null,
				$row->{ChangeLogTableMap::COL_NEW_VALUE_LABEL} !== null ? (string) $row->{ChangeLogTableMap::COL_NEW_VALUE_LABEL} : null,
			),
			'actionClass' => $this->resolveActionClass(
				$row->{ChangeLogTableMap::COL_METADATA} !== null ? (string) $row->{ChangeLogTableMap::COL_METADATA} : null,
				$row->{ChangeLogTableMap::COL_OLD_VALUE_ID} !== null ? (string) $row->{ChangeLogTableMap::COL_OLD_VALUE_ID} : null,
				$row->{ChangeLogTableMap::COL_OLD_VALUE_LABEL} !== null ? (string) $row->{ChangeLogTableMap::COL_OLD_VALUE_LABEL} : null,
				$row->{ChangeLogTableMap::COL_NEW_VALUE_ID} !== null ? (string) $row->{ChangeLogTableMap::COL_NEW_VALUE_ID} : null,
				$row->{ChangeLogTableMap::COL_NEW_VALUE_LABEL} !== null ? (string) $row->{ChangeLogTableMap::COL_NEW_VALUE_LABEL} : null,
			),
		];
	}

	private function resolveSectionLabel(string $context): string
	{
		foreach (self::SECTION_CONTEXT_PATTERNS as $section => $patterns) {
			foreach ($patterns as $pattern) {
				if (str_ends_with($pattern, '%') && str_starts_with($context, substr($pattern, 0, -1))) {
					return self::SECTION_OPTIONS[$section];
				}
				if ($context === $pattern) {
					return self::SECTION_OPTIONS[$section];
				}
			}
		}

		return $context;
	}

	private function resolveContextLabel(string $context): string
	{
		return self::CONTEXT_LABELS[$context] ?? $context;
	}

	private function resolveValueTypeLabel(string $valueType): string
	{
		return self::VALUE_TYPE_LABELS[$valueType] ?? $valueType;
	}

	private function resolveEntityLabel(string $context, string $table, int $entityId): string
	{
		if ($table === FileTableMap::TABLE_NAME) {
			$owner = $this->resolveDocumentOwner($context, $entityId);
			if ($owner !== null) {
				return $owner['label'];
			}
		}

		$cacheKey = $table . ':' . $entityId;
		if (isset($this->entityLabelCache[$cacheKey])) {
			return $this->entityLabelCache[$cacheKey];
		}

		$label = $this->doResolveEntityLabel($table, $entityId);
		$this->entityLabelCache[$cacheKey] = $label;

		return $label;
	}

	private function doResolveEntityLabel(string $table, int $entityId): string
	{
		$allowedTables = [
			OpatrovatelkaTableMap::TABLE_NAME,
			FamilyTableMap::TABLE_NAME,
			TurnusTableMap::TABLE_NAME,
			FamilyProposalTableMap::TABLE_NAME,
			TodoClientTableMap::TABLE_NAME,
			MissingRegistryTableMap::TABLE_NAME,
			PartnerTableMap::TABLE_NAME,
			AgencyTableMap::TABLE_NAME,
			UserTableMap::TABLE_NAME,
			FileTableMap::TABLE_NAME,
			CountryTableMap::TABLE_NAME,
			TranslateTableMap::TABLE_NAME,
		];
		if (!in_array($table, $allowedTables, true)) {
			return '#' . $entityId;
		}

		$row = $this->database->table($table)->get($entityId);
		if (!$row instanceof ActiveRow) {
			return '#' . $entityId;
		}

		return match ($table) {
			OpatrovatelkaTableMap::TABLE_NAME => $this->formatPersonLabel($row, OpatrovatelkaTableMap::COL_SURNAME, OpatrovatelkaTableMap::COL_NAME, OpatrovatelkaTableMap::COL_CLIENT_NUMBER, 'Profil', $entityId),
			FamilyTableMap::TABLE_NAME => $this->formatPersonLabel($row, FamilyTableMap::COL_SURNAME, FamilyTableMap::COL_NAME, FamilyTableMap::COL_CLIENT_NUMBER, 'Rodina/projekt', $entityId),
			PartnerTableMap::TABLE_NAME, AgencyTableMap::TABLE_NAME, CountryTableMap::TABLE_NAME => $this->formatNameLabel($row, 'name', $entityId),
			UserTableMap::TABLE_NAME => $this->formatPersonLabel($row, UserTableMap::COL_SECOND_NAME, UserTableMap::COL_NAME, null, 'Používateľ', $entityId),
			FileTableMap::TABLE_NAME => $this->formatNameLabel($row, FileTableMap::COL_NAME, $entityId),
			TurnusTableMap::TABLE_NAME => $this->formatTurnusLabel($row, $entityId),
			FamilyProposalTableMap::TABLE_NAME => $this->formatProposalLabel($row, $entityId),
			TodoClientTableMap::TABLE_NAME => $this->formatTodoLabel($row, $entityId),
			MissingRegistryTableMap::TABLE_NAME => $this->formatMissingRegistryLabel($row, $entityId),
			TranslateTableMap::TABLE_NAME => 'Preklad #' . $entityId,
		};
	}

	private function resolveEntityNote(string $context, string $table, int $entityId): string
	{
		if ($table !== FileTableMap::TABLE_NAME) {
			return '';
		}

		$file = $this->database->table(FileTableMap::TABLE_NAME)->get($entityId);
		if (!$file instanceof ActiveRow) {
			return '';
		}

		$name = trim((string) ($file->{FileTableMap::COL_NAME} ?? ''));

		return $name !== '' ? 'Súbor: ' . $name : '';
	}

	private function resolveEntityLinkType(string $context, string $table, int $entityId): ?string
	{
		if ($table === FileTableMap::TABLE_NAME) {
			$owner = $this->resolveDocumentOwner($context, $entityId);
			return $owner !== null ? $this->resolveEntityLinkTypeByTable($owner['table']) : null;
		}

		return $this->resolveEntityLinkTypeByTable($table);
	}

	private function resolveEntityLinkTypeByTable(string $table): ?string
	{
		return match ($table) {
			OpatrovatelkaTableMap::TABLE_NAME => 'babysitter',
			FamilyTableMap::TABLE_NAME => 'family',
			TurnusTableMap::TABLE_NAME => 'turnus',
			FamilyProposalTableMap::TABLE_NAME => 'proposal',
			TodoClientTableMap::TABLE_NAME => 'todo',
			PartnerTableMap::TABLE_NAME => 'partner',
			AgencyTableMap::TABLE_NAME => 'agency',
			UserTableMap::TABLE_NAME => 'user',
			CountryTableMap::TABLE_NAME => 'country',
			default => null,
		};
	}

	private function resolveEntityLinkId(string $context, string $table, int $entityId): int
	{
		if ($table === FileTableMap::TABLE_NAME) {
			$owner = $this->resolveDocumentOwner($context, $entityId);
			return $owner['id'] ?? $entityId;
		}

		return $entityId;
	}

	/**
	 * @return array{table:string,id:int,label:string}|null
	 */
	private function resolveDocumentOwner(string $context, int $fileId): ?array
	{
		$cacheKey = $context . ':' . $fileId;
		if (array_key_exists($cacheKey, $this->documentOwnerCache)) {
			return $this->documentOwnerCache[$cacheKey];
		}

		$file = $this->database->table(FileTableMap::TABLE_NAME)->get($fileId);
		if (!$file instanceof ActiveRow) {
			$this->documentOwnerCache[$cacheKey] = null;
			return null;
		}

		$dir = trim((string) ($file->{FileTableMap::COL_DIR} ?? ''));
		$owner = $this->resolveDocumentOwnerFromDir($dir);
		$this->documentOwnerCache[$cacheKey] = $owner;

		return $owner;
	}

	/**
	 * @return array{table:string,id:int,label:string}|null
	 */
	private function resolveDocumentOwnerFromDir(string $dir): ?array
	{
		$ownerMap = [
			'babysitters' => OpatrovatelkaTableMap::TABLE_NAME,
			'families-orders' => FamilyTableMap::TABLE_NAME,
			'families-contracts' => FamilyTableMap::TABLE_NAME,
			'partners' => PartnerTableMap::TABLE_NAME,
			'agencies' => AgencyTableMap::TABLE_NAME,
			'turnus' => TurnusTableMap::TABLE_NAME,
		];

		foreach ($ownerMap as $prefix => $table) {
			$ownerId = $this->extractOwnerIdFromDir($dir, $prefix);
			if ($ownerId === null) {
				continue;
			}

			return [
				'table' => $table,
				'id' => $ownerId,
				'label' => $this->resolveEntityLabel('', $table, $ownerId),
			];
		}

		return null;
	}

	private function extractOwnerIdFromDir(string $dir, string $prefix): ?int
	{
		$expectedPrefix = $prefix . '/';
		if (!str_starts_with($dir, $expectedPrefix)) {
			return null;
		}

		$ownerId = substr($dir, strlen($expectedPrefix));

		return ctype_digit($ownerId) && (int) $ownerId > 0 ? (int) $ownerId : null;
	}

	private function formatPersonLabel(ActiveRow $row, string $surnameColumn, string $nameColumn, ?string $clientNumberColumn, string $fallback, int $id): string
	{
		$name = trim((string) ($row->{$surnameColumn} ?? '') . ' ' . (string) ($row->{$nameColumn} ?? ''));
		$clientNumber = $clientNumberColumn !== null ? trim((string) ($row->{$clientNumberColumn} ?? '')) : '';
		if ($clientNumber !== '') {
			$name .= $name !== '' ? ' (' . $clientNumber . ')' : $clientNumber;
		}

		return $name !== '' ? $name : $fallback . ' #' . $id;
	}

	private function formatNameLabel(ActiveRow $row, string $nameColumn, int $id): string
	{
		$name = trim((string) ($row->{$nameColumn} ?? ''));

		return $name !== '' ? $name : '#' . $id;
	}

	private function formatTurnusLabel(ActiveRow $row, int $id): string
	{
		$familyId = (int) ($row->{TurnusTableMap::COL_FAMILY_ID} ?? 0);
		$babysitterId = (int) ($row->{TurnusTableMap::COL_BABYSITTER_ID} ?? 0);
		$familyLabel = $familyId > 0 ? $this->resolveEntityLabel('', FamilyTableMap::TABLE_NAME, $familyId) : 'Rodina neuvedená';
		$babysitterLabel = $babysitterId > 0 ? $this->resolveEntityLabel('', OpatrovatelkaTableMap::TABLE_NAME, $babysitterId) : 'Opatrovateľka neuvedená';
		$dateFrom = $this->parseDate($row->{TurnusTableMap::COL_DATE_FROM} ?? null)?->format('d.m.Y');
		$dateTo = $this->parseDate($row->{TurnusTableMap::COL_DATE_TO} ?? null)?->format('d.m.Y');
		$range = $dateFrom !== null || $dateTo !== null ? ' (' . ($dateFrom ?? '?') . ' - ' . ($dateTo ?? '?') . ')' : '';

		return 'Turnus #' . $id . ': ' . $familyLabel . ' / ' . $babysitterLabel . $range;
	}

	private function formatProposalLabel(ActiveRow $row, int $id): string
	{
		$familyId = (int) ($row->{FamilyProposalTableMap::COL_FAMILY_ID} ?? 0);
		$babysitterId = (int) ($row->{FamilyProposalTableMap::COL_BABYSITTER_ID} ?? 0);
		$familyLabel = $familyId > 0 ? $this->resolveEntityLabel('', FamilyTableMap::TABLE_NAME, $familyId) : 'Rodina neuvedená';
		$babysitterLabel = $babysitterId > 0 ? $this->resolveEntityLabel('', OpatrovatelkaTableMap::TABLE_NAME, $babysitterId) : 'Opatrovateľka neuvedená';

		return 'Návrh #' . $id . ': ' . $familyLabel . ' / ' . $babysitterLabel;
	}

	private function formatTodoLabel(ActiveRow $row, int $id): string
	{
		$title = trim((string) ($row->{TodoClientTableMap::COL_TITLE} ?? ''));
		$familyId = (int) ($row->{TodoClientTableMap::COL_FAMILY_ID} ?? 0);
		$babysitterId = (int) ($row->{TodoClientTableMap::COL_BABYSITTER_ID} ?? 0);
		$parts = [];
		if ($familyId > 0) {
			$parts[] = $this->resolveEntityLabel('', FamilyTableMap::TABLE_NAME, $familyId);
		}
		if ($babysitterId > 0) {
			$parts[] = $this->resolveEntityLabel('', OpatrovatelkaTableMap::TABLE_NAME, $babysitterId);
		}

		$label = $title !== '' ? $title : 'Úloha #' . $id;
		return $parts !== [] ? $label . ': ' . implode(' / ', $parts) : $label;
	}

	private function formatMissingRegistryLabel(ActiveRow $row, int $id): string
	{
		$userId = (int) ($row->{MissingRegistryTableMap::COL_USER_ID} ?? 0);
		$userLabel = $userId > 0 ? $this->resolveEntityLabel('', UserTableMap::TABLE_NAME, $userId) : 'Používateľ neuvedený';
		$dateFrom = $this->parseDate($row->{MissingRegistryTableMap::COL_DATE_FROM} ?? null)?->format('d.m.Y');
		$dateTo = $this->parseDate($row->{MissingRegistryTableMap::COL_DATE_TO} ?? null)?->format('d.m.Y');
		$range = $dateFrom !== null || $dateTo !== null ? ' (' . ($dateFrom ?? '?') . ' - ' . ($dateTo ?? '?') . ')' : '';

		return 'Neprítomnosť #' . $id . ': ' . $userLabel . $range;
	}

	private function resolveActionLabel(?string $metadata, ?string $oldValueId, ?string $oldValueLabel, ?string $newValueId, ?string $newValueLabel): string
	{
		return match ($this->resolveAction($metadata, $oldValueId, $oldValueLabel, $newValueId, $newValueLabel)) {
			'created' => 'Vytvorené',
			'deleted' => 'Vymazané',
			default => 'Upravené',
		};
	}

	private function resolveActionClass(?string $metadata, ?string $oldValueId, ?string $oldValueLabel, ?string $newValueId, ?string $newValueLabel): string
	{
		return match ($this->resolveAction($metadata, $oldValueId, $oldValueLabel, $newValueId, $newValueLabel)) {
			'created' => 'bg-success',
			'deleted' => 'bg-danger',
			default => 'bg-warning text-dark',
		};
	}

	private function resolveAction(?string $metadata, ?string $oldValueId, ?string $oldValueLabel, ?string $newValueId, ?string $newValueLabel): string
	{
		$metadataAction = $this->getMetadataAction($metadata);
		if (in_array($metadataAction, ['uploaded', 'created', 'added'], true)) {
			return 'created';
		}
		if (in_array($metadataAction, ['deleted', 'removed'], true)) {
			return 'deleted';
		}
		if ($metadataAction === 'updated') {
			return 'updated';
		}

		$hasOldValue = $this->hasAuditValue($oldValueId, $oldValueLabel);
		$hasNewValue = $this->hasAuditValue($newValueId, $newValueLabel);

		if (!$hasOldValue && $hasNewValue) {
			return 'created';
		}
		if ($hasOldValue && !$hasNewValue) {
			return 'deleted';
		}

		return 'updated';
	}

	private function getMetadataAction(?string $metadata): ?string
	{
		if ($metadata === null || trim($metadata) === '') {
			return null;
		}

		$decoded = json_decode($metadata, true);
		if (!is_array($decoded) || !isset($decoded['action']) || !is_scalar($decoded['action'])) {
			return null;
		}

		return (string) $decoded['action'];
	}

	private function hasAuditValue(?string $valueId, ?string $valueLabel): bool
	{
		return ($valueId !== null && $valueId !== '') || ($valueLabel !== null && $valueLabel !== '');
	}

	private function parseDate(mixed $value): ?DateTimeImmutable
	{
		if ($value instanceof DateTimeImmutable) {
			return $value;
		}

		if ($value instanceof DateTimeInterface) {
			return DateTimeImmutable::createFromInterface($value);
		}

		if (!is_scalar($value)) {
			return null;
		}

		$value = trim((string) $value);
		if ($value === '' || str_starts_with($value, '0000-00-00')) {
			return null;
		}

		$dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
		if ($dt !== false) {
			return $dt;
		}

		return $this->dateService->tryCreateFromDb($value);
	}
}
