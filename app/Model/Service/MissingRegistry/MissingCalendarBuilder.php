<?php declare(strict_types=1);

namespace App\Model\Service\MissingRegistry;

use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\DTO\MissingRegistry\Calendar\MissingCalendar;
use App\Model\DTO\MissingRegistry\Calendar\MissingCalendarDay;
use App\Model\DTO\MissingRegistry\Calendar\MissingCalendarEntry;
use App\Model\DTO\MissingRegistry\Calendar\MissingCalendarUser;
use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Repository\MissingRegistryRepository;
use App\Model\Repository\UserRepository;
use App\Model\Utils\Date\DateService;
use App\Model\Utils\Date\HolidayService;
use App\Model\Utils\Date\MonthService;
use App\Model\Utils\Validator\ColorValidator;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;

/**
 * Puts together one month of the team absence calendar: a row per day, a column per person,
 * and the day counts of the month and of the whole year.
 */
final readonly class MissingCalendarBuilder
{
	private const string DEFAULT_USER_COLOR = '#8A2062';

	public function __construct(
		private MissingRegistryRepository $missingRegistryRepository,
		private UserRepository $userRepository,
		private MissingRegistryAccessPolicy $policy,
		private MissingRegistryDayCounter $dayCounter,
		private HolidayService $holidayService,
		private MonthService $monthService,
		private DateService $dateService,
		private StorageDirProvider $storageDirProvider,
		private ClockInterface $clock,
	) {
	}

	public function build(int $year, int $month): MissingCalendar
	{
		$monthDto = $this->monthService->getMonthDTOByNumber($month, $year);
		if ($monthDto === null) {
			throw new InvalidArgumentException('Month must be between 1 and 12.');
		}

		$yearFrom = $this->dateService->getFirstDayOfMonthByYearDate($year, 1);
		$yearTo = $this->dateService->getLastDayOfMontByYearDate($year, 12);
		$today = $this->dateService->createDateTimeImmutable($this->clock->now()->format('Y-m-d'));

		$yearRecords = $this->missingRegistryRepository->findInRange($yearFrom, $yearTo);
		$users = $this->createUsers($yearRecords);
		$userIds = array_map(static fn (MissingCalendarUser $user): int => $user->id, $users);

		$days = [];
		foreach ($monthDto->getDayList() as $date) {
			$days[] = new MissingCalendarDay(
				$date,
				$this->dateService->getDayName($date),
				$this->dateService->isWeekendDay($date),
				$this->holidayService->getHolidayName($date),
				$date->format('Y-m-d') === $today->format('Y-m-d'),
				$this->createEntries($yearRecords, $userIds, $date, $monthDto->getFirstDay()),
			);
		}

		$monthStats = $this->dayCounter->countRange($monthDto->getFirstDay(), $monthDto->getLastDay());

		return new MissingCalendar(
			$year,
			$month,
			$monthDto->getName(),
			$users,
			$days,
			$monthStats['workingDays'],
			$monthStats['weekendDays'],
			$monthStats['holidays'],
			$this->findAbsentNames($users, $today),
			$this->dayCounter->countByUserAndType($yearRecords, $monthDto->getFirstDay(), $monthDto->getLastDay()),
			$this->dayCounter->countByUserAndType($yearRecords, $yearFrom, $yearTo),
		);
	}

	/**
	 * @param list<MissingRegistryRecord> $records
	 * @return list<MissingCalendarUser>
	 */
	private function createUsers(array $records): array
	{
		$recordUserIds = array_values(array_unique(array_map(
			static fn (MissingRegistryRecord $record): int => $record->userId,
			$records,
		)));
		$rows = $this->userRepository->findCalendarUsers($recordUserIds);
		$manageableIds = $this->policy->filterManageableUserIds(array_column($rows, 'id'));

		return array_map(fn (array $row): MissingCalendarUser => new MissingCalendarUser(
			$row['id'],
			$row['name'],
			$row['acronym'],
			preg_match(ColorValidator::PATTERN,$row['color']) === 1 ? $row['color'] : self::DEFAULT_USER_COLOR,
			$row['image'] !== ''
				? $this->storageDirProvider->getUserImages() . '/' . $row['image']
				: $this->storageDirProvider->getUserImagesEmpty(),
			in_array($row['id'], $manageableIds, true),
		), $rows);
	}

	/**
	 * @param list<MissingRegistryRecord> $records
	 * @param list<int> $userIds
	 * @return array<int, list<MissingCalendarEntry>>
	 */
	private function createEntries(array $records, array $userIds, DateTimeImmutable $date, DateTimeImmutable $firstDayOfMonth): array
	{
		$key = $date->format('Y-m-d');
		$entries = [];

		foreach ($records as $record) {
			if (!in_array($record->userId, $userIds, true) || !$record->covers($date)) {
				continue;
			}

			$isFirstDay = $record->dateFrom?->format('Y-m-d') === $key;
			$entries[$record->userId][] = new MissingCalendarEntry(
				$record,
				$isFirstDay,
				$record->getLastDay()?->format('Y-m-d') === $key,
				$isFirstDay || $key === $firstDayOfMonth->format('Y-m-d'),
				$this->dayCounter->isCountedRecordDay($record, $date),
				$this->policy->canEdit($record),
			);
		}

		return $entries;
	}

	/**
	 * People who are away from work today. A duty or a substitution is work, not an absence.
	 *
	 * @param list<MissingCalendarUser> $users
	 * @return list<string>
	 */
	private function findAbsentNames(array $users, DateTimeImmutable $today): array
	{
		$absentUserIds = [];
		foreach ($this->missingRegistryRepository->findInRange($today, $today) as $record) {
			if (array_any($record->types, static fn (MissingType $type): bool => $type->countsWorkingDaysOnly())) {
				$absentUserIds[$record->userId] = true;
			}
		}

		$names = [];
		foreach ($users as $user) {
			if (isset($absentUserIds[$user->id])) {
				$names[] = $user->name;
			}
		}

		return $names;
	}
}
