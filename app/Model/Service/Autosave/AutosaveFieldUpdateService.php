<?php declare(strict_types=1);

namespace App\Model\Service\Autosave;

use App\Model\Repository\ChangeLogRepository;
use App\Model\Table\ActiveTableMap;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\BabysitterDiseaseTableMap;
use App\Model\Table\BabysitterPositionPreferenceTableMap;
use App\Model\Table\BabysitterQualificationTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\FileTableMap;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\PermissionTableMap;
use App\Model\Table\PohlavieTableMap;
use App\Model\Table\SelectAccommodationTypeTableMap;
use App\Model\Table\SelectDiseaseTableMap;
use App\Model\Table\SelectDrivingLicenceTableMap;
use App\Model\Table\SelectEducationTableMap;
use App\Model\Table\SelectFamilyProjectTableMap;
use App\Model\Table\SelectLanguageTableMap;
use App\Model\Table\SelectPaymentPeriodTableMap;
use App\Model\Table\SelectSmokerTableMap;
use App\Model\Table\SelectWorkPositionTableMap;
use App\Model\Table\SelectWorkRoleTableMap;
use App\Model\Table\SelectWorkingStatusTableMap;
use App\Model\Table\SelectWorkStatusStaffTableMap;
use App\Model\Table\SelectYesNoTableMap;
use App\Model\Table\StatusBabysitterTableMap;
use App\Model\Table\StatusComplaintTableMap;
use App\Model\Table\StatusDocumentA1TableMap;
use App\Model\Table\StatusDocumentTableMap;
use App\Model\Table\StatusFaTableMap;
use App\Model\Table\StatusFamilyTableMap;
use App\Model\Table\StatusPartnerTableMap;
use App\Model\Table\StatusProposalTableMap;
use App\Model\Table\StatusTodoTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TodoClientTableMap;
use App\Model\Table\TranslateTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use App\Model\Utils\Date\DateService;
use DateTimeInterface;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Http\IRequest;
use Nette\Security\User;

final class AutosaveFieldUpdateService
{
	/** @var array<string, array<string, mixed>>|null */
	private ?array $definitions = null;

	public function __construct(
		private readonly Explorer $database,
		private readonly DateService $dateService,
		private readonly ChangeLogRepository $changeLogRepository,
		private readonly User $user,
	) {
	}

	public function tryHandleRequest(IRequest $request): bool
	{
		$context = (string) ($request->getPost('__autosave_context') ?? '');
		$field = (string) ($request->getPost('__autosave_field') ?? '');
		$entityId = (int) ($request->getPost('id') ?? 0);

		if ($context === '' || $field === '' || $entityId <= 0) {
			return false;
		}

		return $this->updateField(
			$context,
			$entityId,
			$field,
			$request->getPost('__autosave_value'),
			$request->getPost('__autosave_checked') !== null ? (bool) (int) $request->getPost('__autosave_checked') : null,
			$request->getPost('__autosave_item_id') !== null ? (int) $request->getPost('__autosave_item_id') : null,
		);
	}

	private function updateField(string $context, int $entityId, string $field, mixed $rawValue, ?bool $checked, ?int $itemId): bool
	{
		$definition = $this->getDefinition($context, $field);
		if ($definition === null) {
			return false;
		}

		if (($definition['valueType'] ?? null) === 'junction') {
			$this->updateJunction($definition, $entityId, $itemId, $checked);
			return true;
		}

		$row = $this->database->table($definition['table'])->get($entityId);
		if (!$row instanceof ActiveRow) {
			return true;
		}

		$column = (string) $definition['column'];
		$oldDbValue = $row->{$column} ?? null;
		$newDbValue = $this->normalizeDbValue($definition, $rawValue, $checked);
		if (($definition['valueType'] ?? null) === 'date' && $newDbValue === null && trim($this->stringifyValue($rawValue)) !== '') {
			return false;
		}

		if ($this->sameDbValue($definition, $oldDbValue, $newDbValue)) {
			return true;
		}

		$this->database->table($definition['table'])
			->where('id', $entityId)
			->update([$column => $newDbValue]);

		[$oldValueId, $oldValueLabel] = $this->formatAuditValue($definition, $oldDbValue);
		[$newValueId, $newValueLabel] = $this->formatAuditValue($definition, $newDbValue);

		$this->changeLogRepository->logChange([
			'context' => $context,
			'entityTable' => (string) $definition['table'],
			'entityId' => $entityId,
			'fieldName' => $field,
			'fieldLabel' => (string) $definition['label'],
			'columnName' => $column,
			'valueType' => (string) $definition['valueType'],
			'oldValueId' => $oldValueId,
			'oldValueLabel' => $oldValueLabel,
			'newValueId' => $newValueId,
			'newValueLabel' => $newValueLabel,
			'userId' => $this->getUserId(),
			'metadata' => null,
		]);

		return true;
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function updateJunction(array $definition, int $ownerId, ?int $itemId, ?bool $checked): void
	{
		if ($itemId === null || $itemId <= 0 || $checked === null) {
			return;
		}

		$selection = $this->database->table($definition['table'])
			->where($definition['ownerColumn'], $ownerId)
			->where($definition['valueColumn'], $itemId);
		$exists = $selection->fetch() !== null;

		if ($exists === $checked) {
			return;
		}

		if ($checked) {
			$this->database->table($definition['table'])->insert([
				$definition['ownerColumn'] => $ownerId,
				$definition['valueColumn'] => $itemId,
			]);
		} else {
			$selection->delete();
		}

		$itemLabel = $this->resolveOptionLabel($definition, $itemId);

		$this->changeLogRepository->logChange([
			'context' => (string) $definition['context'],
			'entityTable' => (string) $definition['table'],
			'entityId' => $ownerId,
			'fieldName' => (string) $definition['field'],
			'fieldLabel' => (string) $definition['label'],
			'columnName' => (string) $definition['valueColumn'],
			'valueType' => 'junction',
			'oldValueId' => $checked ? null : (string) $itemId,
			'oldValueLabel' => $checked ? null : $itemLabel,
			'newValueId' => $checked ? (string) $itemId : null,
			'newValueLabel' => $checked ? $itemLabel : null,
			'userId' => $this->getUserId(),
			'metadata' => [
				'action' => $checked ? 'added' : 'removed',
				'item_id' => $itemId,
				'item_label' => $itemLabel,
			],
		]);
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function normalizeDbValue(array $definition, mixed $rawValue, ?bool $checked): mixed
	{
		$valueType = (string) $definition['valueType'];
		$value = is_scalar($rawValue) ? (string) $rawValue : '';

		return match ($valueType) {
			'bool' => $checked !== null ? ($checked ? 1 : 0) : ((int) $value > 0 ? 1 : 0),
			'int', 'select' => (int) $value,
			'date' => $this->dateService->tryCreateFromUserInput($value)?->format('Y-m-d'),
			'float' => $this->normalizeFloatDbValue($value, (bool) ($definition['nullable'] ?? false)),
			default => $value,
		};
	}

	private function normalizeFloatDbValue(string $value, bool $nullable): ?float
	{
		$value = trim(str_replace([' ', "\xc2\xa0"], '', $value));
		$value = str_replace(',', '.', $value);

		if ($value === '') {
			return $nullable ? null : 0.0;
		}

		return (float) $value;
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function sameDbValue(array $definition, mixed $oldValue, mixed $newValue): bool
	{
		if (($definition['valueType'] ?? null) === 'date') {
			return $this->formatDbDate($oldValue) === $this->formatDbDate($newValue);
		}

		if (($definition['valueType'] ?? null) === 'float') {
			if ($oldValue === null || $oldValue === '') {
				return $newValue === null || (!$definition['nullable'] && (float) $newValue === 0.0);
			}

			return $newValue !== null && abs((float) $oldValue - (float) $newValue) < 0.000001;
		}

		return $this->stringifyValue($oldValue) === $this->stringifyValue($newValue);
	}

	/**
	 * @param array<string, mixed> $definition
	 * @return array{0:?string,1:?string}
	 */
	private function formatAuditValue(array $definition, mixed $value): array
	{
		$valueType = (string) $definition['valueType'];

		if ($valueType === 'select' || $valueType === 'bool') {
			$id = (string) (int) $value;
			return [$id, $this->resolveOptionLabel($definition, (int) $value)];
		}

		if ($valueType === 'date') {
			$date = $this->formatDisplayDate($value);
			return [null, $date];
		}

		return [null, $value !== null ? $this->stringifyValue($value) : null];
	}

	private function formatDbDate(mixed $value): string
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format('Y-m-d');
		}

		if ($value === null || $value === '') {
			return '';
		}

		$date = $this->dateService->tryCreateFromDb($this->stringifyValue($value));

		return $date?->format('Y-m-d') ?? $this->stringifyValue($value);
	}

	private function formatDisplayDate(mixed $value): ?string
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format('d.m.Y');
		}

		if ($value === null || $value === '') {
			return null;
		}

		return $this->dateService->tryCreateFromDb($this->stringifyValue($value))?->format('d.m.Y');
	}

	private function stringifyValue(mixed $value): string
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format('Y-m-d H:i:s');
		}

		return (string) ($value ?? '');
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function resolveOptionLabel(array $definition, int $value): string
	{
		if (isset($definition['labels'][$value])) {
			return (string) $definition['labels'][$value];
		}

		if ($value === 0) {
			return '---';
		}

		if (!isset($definition['option'])) {
			return (string) $value;
		}

		$option = $definition['option'];
		$row = $this->database->table($option['table'])
			->where($option['idColumn'], $value)
			->fetch();
		if (!$row instanceof ActiveRow) {
			return (string) $value;
		}

		$labelColumns = (array) $option['labelColumn'];
		$parts = [];
		foreach ($labelColumns as $labelColumn) {
			$part = trim((string) ($row->{$labelColumn} ?? ''));
			if ($part !== '') {
				$parts[] = $part;
			}
		}

		return $parts !== [] ? implode(' ', $parts) : (string) $value;
	}

	private function getUserId(): ?int
	{
		return $this->user->isLoggedIn() && is_int($this->user->getId()) ? (int) $this->user->getId() : null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function getDefinition(string $context, string $field): ?array
	{
		$definitions = $this->getDefinitions();

		return $definitions[$context]['fields'][$field] ?? null;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function getDefinitions(): array
	{
		if ($this->definitions === null) {
			$this->definitions = $this->createDefinitions();
		}

		return $this->definitions;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function createDefinitions(): array
	{
		return [
			'agency.update' => $this->context(AgencyTableMap::TABLE_NAME, [
				'name' => $this->text(AgencyTableMap::COL_NAME, 'Názov firmy'),
				'street' => $this->text(AgencyTableMap::COL_STREET, 'Ulica'),
				'streetNumber' => $this->text(AgencyTableMap::COL_STREET_NUMBER, 'Číslo ulice'),
				'psc' => $this->text(AgencyTableMap::COL_PSC, 'PSČ'),
				'city' => $this->text(AgencyTableMap::COL_CITY, 'Mesto'),
				'state' => $this->select(AgencyTableMap::COL_STATE, 'Krajina', CountryTableMap::TABLE_NAME, CountryTableMap::COL_ID, CountryTableMap::COL_NAME),
				'dateStart' => $this->date(AgencyTableMap::COL_DATE_START, 'Začiatok spolupráce'),
				'personSurname' => $this->text(AgencyTableMap::COL_PERSON_SURNAME, 'Kontaktná osoba - priezvisko'),
				'personName' => $this->text(AgencyTableMap::COL_PERSON_NAME, 'Kontaktná osoba - meno'),
				'ico' => $this->text(AgencyTableMap::COL_ICO, 'IČO'),
				'icDph' => $this->text(AgencyTableMap::COL_IC_DPH, 'IČ DPH'),
				'web' => $this->text(AgencyTableMap::COL_WEB, 'Web'),
				'phone' => $this->text(AgencyTableMap::COL_PHONE, 'Telefónne číslo'),
				'email' => $this->text(AgencyTableMap::COL_EMAIL, 'Email'),
				'status' => $this->select(AgencyTableMap::COL_STATUS, 'Status', StatusPartnerTableMap::TABLE_NAME, StatusPartnerTableMap::COL_ID, StatusPartnerTableMap::COL_STATUS),
				'notice' => $this->text(AgencyTableMap::COL_NOTICE, 'Poznámka'),
			]),
			'partner.update' => $this->context(PartnerTableMap::TABLE_NAME, [
				'name' => $this->text(PartnerTableMap::COL_NAME, 'Názov firmy'),
				'street' => $this->text(PartnerTableMap::COL_STREET, 'Ulica'),
				'streetNumber' => $this->text(PartnerTableMap::COL_STREET_NUMBER, 'Číslo ulice'),
				'psc' => $this->text(PartnerTableMap::COL_PSC, 'PSČ'),
				'city' => $this->text(PartnerTableMap::COL_CITY, 'Mesto'),
				'state' => $this->select(PartnerTableMap::COL_STATE, 'Krajina', CountryTableMap::TABLE_NAME, CountryTableMap::COL_ID, CountryTableMap::COL_NAME),
				'dateStart' => $this->date(PartnerTableMap::COL_DATE_START, 'Začiatok spolupráce'),
				'personSurname' => $this->text(PartnerTableMap::COL_PERSON_SURNAME, 'Kontaktná osoba - priezvisko'),
				'personName' => $this->text(PartnerTableMap::COL_PERSON_NAME, 'Kontaktná osoba - meno'),
				'ico' => $this->text(PartnerTableMap::COL_ICO, 'IČO'),
				'icDph' => $this->text(PartnerTableMap::COL_IC_DPH, 'IČ DPH'),
				'web' => $this->text(PartnerTableMap::COL_WEB, 'Web'),
				'phone' => $this->text(PartnerTableMap::COL_PHONE, 'Telefónne číslo'),
				'email' => $this->text(PartnerTableMap::COL_EMAIL, 'Email'),
				'status' => $this->select(PartnerTableMap::COL_STATUS, 'Status', StatusPartnerTableMap::TABLE_NAME, StatusPartnerTableMap::COL_ID, StatusPartnerTableMap::COL_STATUS),
				'notice' => $this->text(PartnerTableMap::COL_NOTICE, 'Poznámka'),
			]),
			'babysitter.main' => $this->context(OpatrovatelkaTableMap::TABLE_NAME, [
				'type' => $this->select(OpatrovatelkaTableMap::COL_TYPE, 'Typ pracovnej pozície', SelectWorkRoleTableMap::TABLE_NAME, SelectWorkRoleTableMap::COL_ID, SelectWorkRoleTableMap::COL_SLOVAK),
				'agencyId' => $this->select(OpatrovatelkaTableMap::COL_AGENCY_ID, 'Agentúra', AgencyTableMap::TABLE_NAME, AgencyTableMap::COL_ID, AgencyTableMap::COL_NAME),
				'workingStatus' => $this->select(OpatrovatelkaTableMap::COL_WORKING_STATUS, 'Pracovný status', SelectWorkingStatusTableMap::TABLE_NAME, SelectWorkingStatusTableMap::COL_ID, SelectWorkingStatusTableMap::COL_SLOVAK),
				'status' => $this->select(OpatrovatelkaTableMap::COL_STATUS, 'Status', StatusBabysitterTableMap::TABLE_NAME, StatusBabysitterTableMap::COL_ID, StatusBabysitterTableMap::COL_STATUS),
				'firstContactUserId' => $this->select(OpatrovatelkaTableMap::COL_FIRST_CONTACT_USER_ID, 'Prvý kontakt vytvoril', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_SECOND_NAME, UserTableMap::COL_NAME]),
				'blacklist' => $this->select(OpatrovatelkaTableMap::COL_BLACKLIST, 'Blacklist', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
				'notice' => $this->text(OpatrovatelkaTableMap::COL_NOTICE, 'Poznámka'),
			]),
			'babysitter.address' => $this->context(OpatrovatelkaTableMap::TABLE_NAME, [
				'name' => $this->text(OpatrovatelkaTableMap::COL_NAME, 'Meno'),
				'surname' => $this->text(OpatrovatelkaTableMap::COL_SURNAME, 'Priezvisko'),
				'birthday' => $this->date(OpatrovatelkaTableMap::COL_BIRTHDAY, 'Dátum narodenia'),
				'pohlavie' => $this->select(OpatrovatelkaTableMap::COL_POHLAVIE, 'Pohlavie', PohlavieTableMap::TABLE_NAME, PohlavieTableMap::COL_ID, PohlavieTableMap::COL_POHLAVIE),
				'country' => $this->select(OpatrovatelkaTableMap::COL_COUNTRY, 'Národnosť', CountryTableMap::TABLE_NAME, CountryTableMap::COL_ID, CountryTableMap::COL_NAME),
				'city' => $this->text(OpatrovatelkaTableMap::COL_CITY, 'Mesto'),
				'street' => $this->text(OpatrovatelkaTableMap::COL_STREET, 'Ulica'),
				'postalCode' => $this->text(OpatrovatelkaTableMap::COL_POSTAL_CODE, 'PSČ'),
				'phone' => $this->text(OpatrovatelkaTableMap::COL_PHONE, 'Telefón'),
				'phone2' => $this->text(OpatrovatelkaTableMap::COL_PHONE2, 'Telefón č.2'),
				'email' => $this->text(OpatrovatelkaTableMap::COL_EMAIL, 'Email'),
				'height' => $this->text(OpatrovatelkaTableMap::COL_HEIGHT, 'Výška'),
				'weight' => $this->text(OpatrovatelkaTableMap::COL_WEIGHT, 'Váha'),
				'about' => $this->text(OpatrovatelkaTableMap::COL_ABOUT, 'O sebe'),
				'requirements' => $this->text(OpatrovatelkaTableMap::COL_REQUIREMENTS, 'Požiadavky'),
				'contactPersonName' => $this->text(OpatrovatelkaTableMap::COL_CONTACT_PERSON_NAME, 'Kont. osoba - meno'),
				'contactPersonPhone' => $this->text(OpatrovatelkaTableMap::COL_CONTACT_PERSON_PHONE, 'Kont. osoba - telefón'),
			]),
			'babysitter.education' => $this->context(OpatrovatelkaTableMap::TABLE_NAME, [
				'education' => $this->select(OpatrovatelkaTableMap::COL_EDUCATION, 'Vzdelanie', SelectEducationTableMap::TABLE_NAME, SelectEducationTableMap::COL_ID, SelectEducationTableMap::COL_SLOVAK),
				'drivingLicence' => $this->select(OpatrovatelkaTableMap::COL_DRIVING_LICENCE, 'Vodičský preukaz', SelectDrivingLicenceTableMap::TABLE_NAME, SelectDrivingLicenceTableMap::COL_ID, SelectDrivingLicenceTableMap::COL_SLOVAK),
				'readyDrive' => $this->select(OpatrovatelkaTableMap::COL_READY_DRIVE, 'Pripravený riadiť', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
				'languageSkills' => $this->select(OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS, 'Jazykové znalosti', SelectLanguageTableMap::TABLE_NAME, SelectLanguageTableMap::COL_ID, SelectLanguageTableMap::COL_SLOVAK),
				'languageSkillsOther' => $this->text(OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS_OTHER, 'Iné jazyky'),
				'course' => $this->select(OpatrovatelkaTableMap::COL_COURSE, 'Kurzy', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
				'courseDetail' => $this->text(OpatrovatelkaTableMap::COL_COURSE_DETAIL, 'Kurzy - detail'),
			]),
			'babysitter.profile' => $this->context(OpatrovatelkaTableMap::TABLE_NAME, [
				'smoker' => $this->select(OpatrovatelkaTableMap::COL_SMOKER, 'Fajčiar', SelectSmokerTableMap::TABLE_NAME, SelectSmokerTableMap::COL_ID, SelectSmokerTableMap::COL_SLOVAK),
				'allergy' => $this->select(OpatrovatelkaTableMap::COL_ALLERGY, 'Alergie', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
				'dailyCare' => $this->select(OpatrovatelkaTableMap::COL_DAILY_CARE, 'Denná starostlivosť', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
				'hourlyCare' => $this->select(OpatrovatelkaTableMap::COL_HOURLY_CARE, 'Hodinová starostlivosť', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
				'accommodationType' => $this->select(OpatrovatelkaTableMap::COL_ACCOMMODATION_TYPE, 'Ubytovanie', SelectAccommodationTypeTableMap::TABLE_NAME, SelectAccommodationTypeTableMap::COL_ID, SelectAccommodationTypeTableMap::COL_ACCOMMODATION_TYPE),
				'workShoes' => $this->select(OpatrovatelkaTableMap::COL_WORK_SHOES, 'Pracovná obuv', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
				'allergyDetail' => $this->text(OpatrovatelkaTableMap::COL_ALLERGY_DETAIL, 'Alergie - detail'),
				'howLongWork' => $this->text(OpatrovatelkaTableMap::COL_HOW_LONG_WORK, 'Prax'),
				'howLongWorkGerman' => $this->text(OpatrovatelkaTableMap::COL_HOW_LONG_WORK_GERMAN, 'Prax v nemeckých krajinách'),
				'timeScale' => $this->text(OpatrovatelkaTableMap::COL_TIME_SCALE, 'Časové rozmedzie'),
				'workPlace' => $this->text(OpatrovatelkaTableMap::COL_WORK_PLACE, 'Miesto práce'),
				'jobPositionInterest' => $this->text(OpatrovatelkaTableMap::COL_JOB_POSITION_INTEREST, 'Záujem o pracovnú pozíciu'),
				'workDescription' => $this->text(OpatrovatelkaTableMap::COL_WORK_DESCRIPTION, 'Popis práce'),
				'generalActivities' => $this->text(OpatrovatelkaTableMap::COL_GENERAL_ACTIVITIES, 'Všeobecné činnosti'),
				'ratingAgency' => $this->text(OpatrovatelkaTableMap::COL_RATING_AGENCY, 'Hodnotenie agentúry'),
				'shoeSize' => $this->text(OpatrovatelkaTableMap::COL_SHOE_SIZE, 'Veľkosť pracovnej obuvi'),
				'germanTaxId' => $this->text(OpatrovatelkaTableMap::COL_GERMAN_TAX_ID, 'Nemecké daňové číslo'),
				'diseaseIds' => $this->junction('babysitter.profile', 'diseaseIds', BabysitterDiseaseTableMap::TABLE_NAME, BabysitterDiseaseTableMap::COL_BABYSITTER_ID, BabysitterDiseaseTableMap::COL_DISEASE_ID, 'Skúsenosti s chorobami', SelectDiseaseTableMap::TABLE_NAME, SelectDiseaseTableMap::COL_ID, SelectDiseaseTableMap::COL_SLOVAK),
			]),
			'babysitter.pdf' => $this->context(OpatrovatelkaTableMap::TABLE_NAME, [
				'profilShowContact' => $this->select(OpatrovatelkaTableMap::COL_PROFIL_SHOW_CONTACT, 'Zobraziť kontakt v PDF', SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS),
			]),
			'babysitter.workProfile' => $this->context(OpatrovatelkaTableMap::TABLE_NAME, [
				'qualificationIds' => $this->junction('babysitter.workProfile', 'qualificationIds', BabysitterQualificationTableMap::TABLE_NAME, BabysitterQualificationTableMap::COL_BABYSITTER_ID, BabysitterQualificationTableMap::COL_WORK_POSITION_ID, 'Kvalifikácia', SelectWorkPositionTableMap::TABLE_NAME, SelectWorkPositionTableMap::COL_ID, SelectWorkPositionTableMap::COL_POSITION),
				'preferenceIds' => $this->junction('babysitter.workProfile', 'preferenceIds', BabysitterPositionPreferenceTableMap::TABLE_NAME, BabysitterPositionPreferenceTableMap::COL_BABYSITTER_ID, BabysitterPositionPreferenceTableMap::COL_WORK_POSITION_ID, 'Má záujem o', SelectWorkPositionTableMap::TABLE_NAME, SelectWorkPositionTableMap::COL_ID, SelectWorkPositionTableMap::COL_POSITION),
			]),
			'family.shortInfo' => $this->context(FamilyTableMap::TABLE_NAME, [
				'deProjectNumber' => $this->text(FamilyTableMap::COL_DE_PROJECT_NUMBER, 'Číslo DE projektu'),
				'state' => $this->select(FamilyTableMap::COL_STATE, 'Krajina', CountryTableMap::TABLE_NAME, CountryTableMap::COL_ID, CountryTableMap::COL_NAME),
			]),
			'family.info' => $this->context(FamilyTableMap::TABLE_NAME, [
				'type' => $this->select(FamilyTableMap::COL_TYPE, 'Rodina / Projekt', SelectFamilyProjectTableMap::TABLE_NAME, SelectFamilyProjectTableMap::COL_ID, SelectFamilyProjectTableMap::COL_SLOVAK),
				'partnerId' => $this->select(FamilyTableMap::COL_PARTNER_ID, 'Partner', PartnerTableMap::TABLE_NAME, PartnerTableMap::COL_ID, PartnerTableMap::COL_NAME),
				'acquiredByUserId' => $this->select(FamilyTableMap::COL_ACQUIRED_BY_USER_ID, 'Rodinu získal', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_SECOND_NAME, UserTableMap::COL_NAME]),
				'userId' => $this->select(FamilyTableMap::COL_USER_ID, 'Spravuje', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_SECOND_NAME, UserTableMap::COL_NAME]),
				'status' => $this->select(FamilyTableMap::COL_STATUS, 'Status', StatusFamilyTableMap::TABLE_NAME, StatusFamilyTableMap::COL_ID, StatusFamilyTableMap::COL_STATUS),
				'phone' => $this->text(FamilyTableMap::COL_PHONE, 'Telefónne číslo'),
				'dateStart' => $this->date(FamilyTableMap::COL_DATE_START, 'Začiatok spolupráce'),
				'dateTo' => $this->date(FamilyTableMap::COL_DATE_TO, 'Koniec spolupráce'),
				'orderStatus' => $this->select(FamilyTableMap::COL_ORDER_STATUS, 'Status objednávky', StatusDocumentTableMap::TABLE_NAME, StatusDocumentTableMap::COL_ID, StatusDocumentTableMap::COL_STATUS),
				'contractStatus' => $this->select(FamilyTableMap::COL_CONTRACT_STATUS, 'Status zmluvy', StatusDocumentTableMap::TABLE_NAME, StatusDocumentTableMap::COL_ID, StatusDocumentTableMap::COL_STATUS),
				'workStatusStaff' => $this->select(FamilyTableMap::COL_WORK_STATUS_STAFF, 'Pracovný status personálu', SelectWorkStatusStaffTableMap::TABLE_NAME, SelectWorkStatusStaffTableMap::COL_ID, SelectWorkStatusStaffTableMap::COL_CONTRACT),
				'projectDescription' => $this->text(FamilyTableMap::COL_PROJECT_DESCRIPTION, 'Popis projektu'),
				'projectPositions' => $this->text(FamilyTableMap::COL_PROJECT_POSITIONS, 'Pozície na obsadenie'),
				'projectAvailablePositions' => $this->text(FamilyTableMap::COL_PROJECT_AVAILABLE_POSITIONS, 'Počet voľných pracovných miest'),
			]),
			'family.address' => $this->context(FamilyTableMap::TABLE_NAME, [
				'companyName' => $this->text(FamilyTableMap::COL_COMPANY_NAME, 'Meno spoločnosti'),
				'name' => $this->text(FamilyTableMap::COL_NAME, 'Meno'),
				'surname' => $this->text(FamilyTableMap::COL_SURNAME, 'Priezvisko'),
				'street' => $this->text(FamilyTableMap::COL_STREET, 'Ulica'),
				'streetNumber' => $this->text(FamilyTableMap::COL_STREET_NUMBER, 'Číslo ulice'),
				'psc' => $this->text(FamilyTableMap::COL_PSC, 'PSČ'),
				'city' => $this->text(FamilyTableMap::COL_CITY, 'Mesto'),
				'billing' => $this->text(FamilyTableMap::COL_BILLING, 'Fakturačné údaje'),
				'employer' => $this->text(FamilyTableMap::COL_EMPLOYER, 'Zamestnávateľ'),
				'accommodationAddress' => $this->text(FamilyTableMap::COL_ACCOMMODATION_ADDRESS, 'Adresa ubytovania'),
				'notice' => $this->text(FamilyTableMap::COL_NOTICE, 'Poznámka'),
				'personSurname' => $this->text(FamilyTableMap::COL_PERSON_SURNAME, 'Kontaktná osoba - priezvisko'),
				'personName' => $this->text(FamilyTableMap::COL_PERSON_NAME, 'Kontaktná osoba - meno'),
				'personPhone' => $this->text(FamilyTableMap::COL_PERSON_PHONE, 'Kontaktná osoba - telefónne číslo'),
				'personEmail' => $this->text(FamilyTableMap::COL_PERSON_EMAIL, 'Kontaktná osoba - email'),
				'patientPhone' => $this->text(FamilyTableMap::COL_PATIENT_PHONE, 'Pacient - telefónne číslo'),
			]),
			'turnus.update' => $this->context(TurnusTableMap::TABLE_NAME, [
				'status' => $this->select(TurnusTableMap::COL_STATUS, 'Status', StatusTurnusTableMap::TABLE_NAME, StatusTurnusTableMap::COL_ID, StatusTurnusTableMap::COL_STATUS),
				'familyId' => $this->select(TurnusTableMap::COL_FAMILY_ID, 'Rodina', FamilyTableMap::TABLE_NAME, FamilyTableMap::COL_ID, [FamilyTableMap::COL_SURNAME, FamilyTableMap::COL_NAME, FamilyTableMap::COL_CLIENT_NUMBER]),
				'babysitterId' => $this->select(TurnusTableMap::COL_BABYSITTER_ID, 'Opatrovateľka', OpatrovatelkaTableMap::TABLE_NAME, OpatrovatelkaTableMap::COL_ID, [OpatrovatelkaTableMap::COL_SURNAME, OpatrovatelkaTableMap::COL_NAME, OpatrovatelkaTableMap::COL_CLIENT_NUMBER]),
				'dateFrom' => $this->date(TurnusTableMap::COL_DATE_FROM, 'Nástup'),
				'dateTo' => $this->date(TurnusTableMap::COL_DATE_TO, 'Ukončenie'),
				'userId' => $this->select(TurnusTableMap::COL_USER_ID, 'Spracováva', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_NAME, UserTableMap::COL_SECOND_NAME]),
				'agencyId' => $this->select(TurnusTableMap::COL_AGENCY_ID, 'Agentúra', AgencyTableMap::TABLE_NAME, AgencyTableMap::COL_ID, AgencyTableMap::COL_NAME),
				'partnerId' => $this->select(TurnusTableMap::COL_PARTNER_ID, 'Partner', PartnerTableMap::TABLE_NAME, PartnerTableMap::COL_ID, PartnerTableMap::COL_NAME),
				'workingStatus' => $this->select(TurnusTableMap::COL_WORKING_STATUS, 'Status zamestnanca', SelectWorkingStatusTableMap::TABLE_NAME, SelectWorkingStatusTableMap::COL_ID, SelectWorkingStatusTableMap::COL_SLOVAK),
				'workPositionId' => $this->select(TurnusTableMap::COL_WORK_POSITION_ID, 'Pracovná pozícia', SelectWorkPositionTableMap::TABLE_NAME, SelectWorkPositionTableMap::COL_ID, SelectWorkPositionTableMap::COL_POSITION),
				'preinvoiceNumber' => $this->text(TurnusTableMap::COL_PREINVOICE_NUMBER, 'Číslo PFA'),
				'invoiceNumber' => $this->text(TurnusTableMap::COL_INVOICE_NUMBER, 'Číslo FA'),
				'invoiceStatus' => $this->select(TurnusTableMap::COL_INVOICE_STATUS, 'Status FA', StatusFaTableMap::TABLE_NAME, StatusFaTableMap::COL_ID, StatusFaTableMap::COL_STATUS),
				'fee' => $this->float(TurnusTableMap::COL_FEE, 'Honorár DLV'),
				'feeAg' => $this->float(TurnusTableMap::COL_FEE_AG, 'Honorár AG'),
				'feeBk' => $this->float(TurnusTableMap::COL_FEE_BK, 'Honorár BK'),
				'travelCostsArrival' => $this->float(TurnusTableMap::COL_TRAVEL_COSTS_ARRIVAL, 'Suma - príchod'),
				'travelCostsDeparture' => $this->float(TurnusTableMap::COL_TRAVEL_COSTS_DEPARTURE, 'Suma - odchod'),
				'travelExpenses' => $this->text(TurnusTableMap::COL_TRAVEL_EXPENSES, 'Cestovné'),
				'bonus' => $this->float(TurnusTableMap::COL_BONUS, 'Bonus'),
				'holiday' => $this->float(TurnusTableMap::COL_HOLIDAY, 'Sviatky'),
				'sva' => $this->text(TurnusTableMap::COL_SVA, 'SVA'),
				'commissionComplet' => $this->float(TurnusTableMap::COL_COMMISSION_COMPLET, 'Provízia - komplet'),
				'commissionPartners' => $this->float(TurnusTableMap::COL_COMMISSION_PARTNERS, 'Partneri'),
				'paymentPeriodPartner' => $this->select(TurnusTableMap::COL_PAYMENT_PERIOD_PARTNER, 'Platba partner', SelectPaymentPeriodTableMap::TABLE_NAME, SelectPaymentPeriodTableMap::COL_ID, SelectPaymentPeriodTableMap::COL_STATUS),
				'commission4ms' => $this->float(TurnusTableMap::COL_COMMISSION_4MS, '4MS'),
				'paymentPeriod' => $this->select(TurnusTableMap::COL_PAYMENT_PERIOD, 'Platba', SelectPaymentPeriodTableMap::TABLE_NAME, SelectPaymentPeriodTableMap::COL_ID, SelectPaymentPeriodTableMap::COL_STATUS),
				'remainingPayment' => $this->float(TurnusTableMap::COL_REMAINING_PAYMENT, 'Zostávajúca platba', true),
				'notice' => $this->text(TurnusTableMap::COL_NOTICE, 'Poznámka'),
				'complaint' => $this->text(TurnusTableMap::COL_COMPLAINT, 'Reklamácia'),
				'complaintStatus' => $this->select(TurnusTableMap::COL_COMPLAINT_STATUS, 'Status reklamácie', StatusComplaintTableMap::TABLE_NAME, StatusComplaintTableMap::COL_ID, StatusComplaintTableMap::COL_STATUS),
			]),
			'turnus.statusA1' => $this->context(TurnusTableMap::TABLE_NAME, [
				'statusA1' => $this->select(TurnusTableMap::COL_STATUS_A1, 'Status A1', StatusDocumentA1TableMap::TABLE_NAME, StatusDocumentA1TableMap::COL_ID, StatusDocumentA1TableMap::COL_STATUS),
			]),
			'todo.update' => $this->context(TodoClientTableMap::TABLE_NAME, [
				'familyId' => $this->select(TodoClientTableMap::COL_FAMILY_ID, 'Rodina', FamilyTableMap::TABLE_NAME, FamilyTableMap::COL_ID, [FamilyTableMap::COL_SURNAME, FamilyTableMap::COL_NAME, FamilyTableMap::COL_CLIENT_NUMBER]),
				'babysitterId' => $this->select(TodoClientTableMap::COL_BABYSITTER_ID, 'Opatrovateľka', OpatrovatelkaTableMap::TABLE_NAME, OpatrovatelkaTableMap::COL_ID, [OpatrovatelkaTableMap::COL_SURNAME, OpatrovatelkaTableMap::COL_NAME, OpatrovatelkaTableMap::COL_CLIENT_NUMBER]),
				'todoFromUser' => $this->select(TodoClientTableMap::COL_TODO_FROM_USER, 'Úlohu zadal', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_NAME, UserTableMap::COL_SECOND_NAME]),
				'todoToUser1' => $this->select(TodoClientTableMap::COL_TODO_TO_USER_1, 'Úlohu spracováva 1', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_NAME, UserTableMap::COL_SECOND_NAME]),
				'todoToUser2' => $this->select(TodoClientTableMap::COL_TODO_TO_USER_2, 'Úlohu spracováva 2', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_NAME, UserTableMap::COL_SECOND_NAME]),
				'todoCreated' => $this->date(TodoClientTableMap::COL_TODO_CREATED, 'Dátum vytvorenia'),
				'todoDeadline' => $this->date(TodoClientTableMap::COL_TODO_DEADLINE, 'Deadline spracovania'),
				'status' => $this->select(TodoClientTableMap::COL_STATUS, 'Status', StatusTodoTableMap::TABLE_NAME, StatusTodoTableMap::COL_ID, StatusTodoTableMap::COL_STATUS),
				'title' => $this->text(TodoClientTableMap::COL_TITLE, 'Názov úlohy'),
				'description' => $this->text(TodoClientTableMap::COL_DESCRIPTION, 'Popis'),
				'answer' => $this->text(TodoClientTableMap::COL_ANSWER, 'Odpoveď'),
			]),
			'proposal.update' => $this->context(FamilyProposalTableMap::TABLE_NAME, [
				'status' => $this->select(FamilyProposalTableMap::COL_STATUS, 'Status', StatusProposalTableMap::TABLE_NAME, StatusProposalTableMap::COL_ID, StatusProposalTableMap::COL_STATUS),
				'babysitterId' => $this->select(FamilyProposalTableMap::COL_BABYSITTER_ID, 'Opatrovateľka', OpatrovatelkaTableMap::TABLE_NAME, OpatrovatelkaTableMap::COL_ID, [OpatrovatelkaTableMap::COL_SURNAME, OpatrovatelkaTableMap::COL_NAME, OpatrovatelkaTableMap::COL_CLIENT_NUMBER]),
				'dateStartingWork' => $this->date(FamilyProposalTableMap::COL_DATE_STARTING_WORK, 'Kedy môže nastúpiť'),
				'dateProposalSended' => $this->date(FamilyProposalTableMap::COL_DATE_PROPOSAL_SENDED, 'Odoslané klientovi'),
				'notice' => $this->text(FamilyProposalTableMap::COL_NOTICE, 'Poznámka'),
			]),
			'missingRegistry.row' => $this->context(MissingRegistryTableMap::TABLE_NAME, [
				'userId' => $this->select(MissingRegistryTableMap::COL_USER_ID, 'Kto', UserTableMap::TABLE_NAME, UserTableMap::COL_ID, [UserTableMap::COL_NAME, UserTableMap::COL_SECOND_NAME]),
				'dateFrom' => $this->date(MissingRegistryTableMap::COL_DATE_FROM, 'Od'),
				'dateTo' => $this->date(MissingRegistryTableMap::COL_DATE_TO, 'Do'),
				'typePn' => $this->bool(MissingRegistryTableMap::COL_TYPE_PN, 'PN'),
				'typeOcr' => $this->bool(MissingRegistryTableMap::COL_TYPE_OCR, 'OČR'),
				'typeLekar' => $this->bool(MissingRegistryTableMap::COL_TYPE_LEKAR, 'Lekár'),
				'typeSviatok' => $this->bool(MissingRegistryTableMap::COL_TYPE_SVIATOK, 'Sviatok'),
				'typeZastup' => $this->bool(MissingRegistryTableMap::COL_TYPE_ZASTUP, 'Zástup'),
				'typeSluzba' => $this->bool(MissingRegistryTableMap::COL_TYPE_SLUZBA, 'Služba'),
				'typeDovolenka' => $this->bool(MissingRegistryTableMap::COL_TYPE_DOVOLENKA, 'Dovolenka'),
				'notice' => $this->text(MissingRegistryTableMap::COL_NOTICE, 'Poznámka'),
			]),
			'country.update' => $this->context(CountryTableMap::TABLE_NAME, [
				'name' => $this->text(CountryTableMap::COL_NAME, 'Názov'),
				'german' => $this->text(CountryTableMap::COL_GERMAN, 'Nemecky'),
			]),
			'translation.update' => $this->context(TranslateTableMap::TABLE_NAME, [
				'german' => $this->text(TranslateTableMap::COL_GERMAN, 'Nemecky'),
			]),
			'user.profile' => $this->context(UserTableMap::TABLE_NAME, [
				'name' => $this->text(UserTableMap::COL_NAME, 'Meno'),
				'secondName' => $this->text(UserTableMap::COL_SECOND_NAME, 'Priezvisko'),
				'acronym' => $this->text(UserTableMap::COL_ACRONYM, 'Skratka'),
				'email' => $this->text(UserTableMap::COL_EMAIL, 'Email'),
				'color' => $this->text(UserTableMap::COL_COLOR, 'Farba'),
			]),
			'user.access' => $this->context(UserTableMap::TABLE_NAME, [
				'permission' => $this->select(UserTableMap::COL_PERMISSION, 'Práva', PermissionTableMap::TABLE_NAME, PermissionTableMap::COL_PERMISSION, PermissionTableMap::COL_NAME),
				'active' => $this->select(UserTableMap::COL_ACTIVE, 'Aktívny', ActiveTableMap::TABLE_NAME, ActiveTableMap::COL_ID, ActiveTableMap::COL_STATUS),
			]),
			'documents.babysitter' => $this->documentContext(),
			'documents.family' => $this->documentContext(),
			'documents.agency' => $this->documentContext(),
			'documents.partner' => $this->documentContext(),
			'documents.turnus' => $this->documentContext(false),
		];
	}

	/**
	 * @param array<string, array<string, mixed>> $fields
	 * @return array{fields: array<string, mixed>}
	 */
	private function context(string $table, array $fields): array
	{
		foreach ($fields as &$field) {
			if (($field['valueType'] ?? null) !== 'junction') {
				$field['table'] = $table;
			}
		}
		unset($field);

		return ['fields' => $fields];
	}

	/**
	 * @return array{fields: array<string, mixed>}
	 */
	private function documentContext(bool $withStatus = true): array
	{
		$fields = [
			'notice' => $this->text(FileTableMap::COL_NOTICE, 'Poznámka'),
			'validFrom' => $this->date(FileTableMap::COL_VALID_FROM, 'Platnosť od'),
			'validTo' => $this->date(FileTableMap::COL_VALID_TO, 'Platnosť do'),
		];
		if ($withStatus) {
			$fields['status'] = $this->select(FileTableMap::COL_STATUS, 'Status', StatusDocumentTableMap::TABLE_NAME, StatusDocumentTableMap::COL_ID, StatusDocumentTableMap::COL_STATUS);
		}

		return $this->context(FileTableMap::TABLE_NAME, $fields);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function text(string $column, string $label): array
	{
		return ['column' => $column, 'label' => $label, 'valueType' => 'text'];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function date(string $column, string $label): array
	{
		return ['column' => $column, 'label' => $label, 'valueType' => 'date'];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function bool(string $column, string $label): array
	{
		return ['column' => $column, 'label' => $label, 'valueType' => 'bool', 'labels' => [0 => 'Nie', 1 => 'Áno']];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function float(string $column, string $label, bool $nullable = false): array
	{
		return ['column' => $column, 'label' => $label, 'valueType' => 'float', 'nullable' => $nullable];
	}

	/**
	 * @param string|list<string> $labelColumn
	 * @return array<string, mixed>
	 */
	private function select(string $column, string $label, string $optionTable, string $optionIdColumn, string|array $labelColumn): array
	{
		return [
			'column' => $column,
			'label' => $label,
			'valueType' => 'select',
			'option' => [
				'table' => $optionTable,
				'idColumn' => $optionIdColumn,
				'labelColumn' => $labelColumn,
			],
		];
	}

	/**
	 * @param string|list<string> $labelColumn
	 * @return array<string, mixed>
	 */
	private function junction(
		string $context,
		string $field,
		string $table,
		string $ownerColumn,
		string $valueColumn,
		string $label,
		string $optionTable,
		string $optionIdColumn,
		string|array $labelColumn,
	): array {
		return [
			'context' => $context,
			'field' => $field,
			'table' => $table,
			'ownerColumn' => $ownerColumn,
			'valueColumn' => $valueColumn,
			'label' => $label,
			'valueType' => 'junction',
			'option' => [
				'table' => $optionTable,
				'idColumn' => $optionIdColumn,
				'labelColumn' => $labelColumn,
			],
		];
	}
}
