<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\DTO\Pdf\BabysitterPdfData;
use App\Model\Table\BabysitterDiseaseTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\SelectDiseaseTableMap;
use App\Model\Table\SelectEducationTableMap;
use App\Model\Table\SelectLanguageTableMap;
use App\Model\Table\SelectSmokerTableMap;
use App\Model\Table\SelectYesNoTableMap;
use App\Model\Table\TranslateTableMap;
use App\Model\Utils\Date\DateService;
use Nette\Database\Explorer;

class BabysitterPdfRepository extends BaseRepository
{
	public function __construct(
		Explorer $database,
		DateService $dateService,
		private readonly BabysitterRepository $babysitterRepository,
	) {
		parent::__construct($database, $dateService);
	}

	protected function getTableName(): string
	{
		return OpatrovatelkaTableMap::TABLE_NAME;
	}

	public function findPdfData(int $babysitterId): ?BabysitterPdfData
	{
		$babysitter = $this->babysitterRepository->findUpdateRow($babysitterId);
		if ($babysitter === null) {
			return null;
		}

		$language = $this->findLanguage((int) $babysitter['languageSkills']);

		return new BabysitterPdfData(
			babysitter: $babysitter,
			countryName: $this->findColumnValue(CountryTableMap::TABLE_NAME, (int) $babysitter['country'], CountryTableMap::COL_NAME),
			smokerGerman: $this->findColumnValue(SelectSmokerTableMap::TABLE_NAME, (int) $babysitter['smoker'], SelectSmokerTableMap::COL_GERMAN),
			allergyGerman: $this->findColumnValue(SelectYesNoTableMap::TABLE_NAME, (int) $babysitter['allergy'], SelectYesNoTableMap::COL_GERMAN),
			driverLicenceGerman: $this->findColumnValue(SelectYesNoTableMap::TABLE_NAME, (int) $babysitter['drivingLicence'], SelectYesNoTableMap::COL_GERMAN),
			readyDriveGerman: $this->findColumnValue(SelectYesNoTableMap::TABLE_NAME, (int) $babysitter['readyDrive'], SelectYesNoTableMap::COL_GERMAN),
			educationGerman: $this->findColumnValue(SelectEducationTableMap::TABLE_NAME, (int) $babysitter['education'], SelectEducationTableMap::COL_GERMAN),
			languageGerman: $language['german'],
			languageStars: $language['stars'],
			diseases: $this->findDiseases(),
			selectedDiseaseIds: $this->findSelectedDiseaseIds($babysitterId),
			translations: $this->findTranslations(),
		);
	}

	private function findColumnValue(string $table, int $id, string $column): string
	{
		if ($id <= 0) {
			return '';
		}

		$row = $this->database->table($table)->get($id);
		if ($row === null) {
			return '';
		}

		return (string) ($row->{$column} ?? '');
	}

	/**
	 * @return array{german:string,stars:int}
	 */
	private function findLanguage(int $id): array
	{
		if ($id <= 0) {
			return ['german' => '', 'stars' => 1];
		}

		$row = $this->database->table(SelectLanguageTableMap::TABLE_NAME)->get($id);
		if ($row === null) {
			return ['german' => '', 'stars' => 1];
		}

		return [
			'german' => (string) ($row->{SelectLanguageTableMap::COL_GERMAN} ?? ''),
			'stars' => (int) ($row->{SelectLanguageTableMap::COL_STARS} ?? 1),
		];
	}

	/**
	 * @return list<array{id:int,german:string}>
	 */
	private function findDiseases(): array
	{
		$rows = $this->database->table(SelectDiseaseTableMap::TABLE_NAME)
			->order(SelectDiseaseTableMap::COL_ID . ' ASC')
			->fetchAll();

		return array_values(array_map(
			static fn ($row): array => [
				'id' => (int) $row->{SelectDiseaseTableMap::COL_ID},
				'german' => (string) ($row->{SelectDiseaseTableMap::COL_GERMAN} ?? ''),
			],
			$rows,
		));
	}

	/**
	 * @return list<int>
	 */
	private function findSelectedDiseaseIds(int $babysitterId): array
	{
		$rows = $this->database->table(BabysitterDiseaseTableMap::TABLE_NAME)
			->where(BabysitterDiseaseTableMap::COL_BABYSITTER_ID, $babysitterId)
			->fetchAll();

		return array_values(array_map(
			static fn ($row): int => (int) $row->{BabysitterDiseaseTableMap::COL_DISEASE_ID},
			$rows,
		));
	}

	/**
	 * @return array<int, array{slovak:string,german:string}>
	 */
	private function findTranslations(): array
	{
		$rows = $this->database->table(TranslateTableMap::TABLE_NAME)->fetchAll();
		$result = [];
		foreach ($rows as $row) {
			$result[(int) $row->{TranslateTableMap::COL_ID}] = [
				'slovak' => (string) ($row->{TranslateTableMap::COL_SLOVAK} ?? ''),
				'german' => (string) ($row->{TranslateTableMap::COL_GERMAN} ?? ''),
			];
		}

		return $result;
	}
}
