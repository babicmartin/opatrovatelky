<?php declare(strict_types=1);

namespace App\Model\Service\MissingRegistry;

use App\Model\DTO\MissingRegistry\MissingRegistryInput;
use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Repository\MissingRegistryRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\MissingRegistryTableMap;
use DateTimeImmutable;

/**
 * The only way a registry record is created, changed or deleted by a form.
 * Every method checks the right of the logged-in user first and works with the record
 * it loaded itself, never with an identifier or an owner taken from the request as is.
 *
 * Field-by-field autosave writes through AutosaveFieldUpdateService; it must call
 * assertFieldChangeAllowed() before it does.
 */
final readonly class MissingRegistryWriteService
{
	public const string AUDIT_CONTEXT = 'missingRegistry.row';
	public const int MAX_RANGE_DAYS = 366;
	public const int MAX_NOTICE_LENGTH = 2000;

	/** Name of the autosave field that changes the owner of a record. */
	private const string OWNER_FIELD = 'userId';
	private const string AUDIT_LABEL = 'Neprítomnosť';
	private const int AUDIT_NOTICE_LENGTH = 120;
	private const int MIN_YEAR = 2000;
	private const int MAX_YEAR = 2100;

	public function __construct(
		private MissingRegistryRepository $repository,
		private MissingRegistryAccessPolicy $policy,
		private ChangeAuditLogger $changeAuditLogger,
	) {
	}

	/**
	 * @throws MissingRegistryException when the record does not exist or belongs to somebody else
	 */
	public function getEditable(int $id): MissingRegistryRecord
	{
		$this->assertCanView();

		$record = $id > 0 ? $this->repository->findActive($id) : null;
		if ($record === null) {
			throw MissingRegistryException::notFound();
		}
		if (!$this->policy->canEdit($record)) {
			throw MissingRegistryException::accessDenied();
		}

		return $record;
	}

	/**
	 * Empty row of the list page, filled in afterwards field by field.
	 */
	public function createEmpty(): int
	{
		$this->assertCanView();

		$id = $this->repository->createEmpty($this->policy->canManageAll() ? 0 : $this->policy->getCurrentUserId());
		$this->changeAuditLogger->logCreated(self::AUDIT_CONTEXT, MissingRegistryTableMap::TABLE_NAME, $id, self::AUDIT_LABEL);

		return $id;
	}

	/**
	 * @throws MissingRegistryException
	 */
	public function create(MissingRegistryInput $input): int
	{
		$this->assertCanView();

		$userId = $this->resolveUserId($input, null, true);
		$input = $this->normalize($input, true);

		$id = $this->repository->create($userId, $input);
		$this->changeAuditLogger->logCreated(
			self::AUDIT_CONTEXT,
			MissingRegistryTableMap::TABLE_NAME,
			$id,
			$this->describe($userId, $input),
		);

		return $id;
	}

	/**
	 * $requireComplete: the record must end up with an owner, a date and a type (the calendar).
	 * The list page saves rows that are still being filled in, so it passes false.
	 *
	 * @throws MissingRegistryException
	 */
	public function update(int $id, MissingRegistryInput $input, bool $requireComplete = true): void
	{
		$record = $this->getEditable($id);
		$userId = $this->resolveUserId($input, $record, $requireComplete);
		$input = $this->normalize($input, $requireComplete);

		if (!$this->repository->updateRecord($record->id, $userId, $input)) {
			throw MissingRegistryException::notFound();
		}

		$old = $this->describe($record->userId, new MissingRegistryInput($record->userId, $record->dateFrom, $record->dateTo, $record->types, $record->notice));
		$new = $this->describe($userId, $input);
		if ($old !== $new) {
			$this->changeAuditLogger->logUpdated(self::AUDIT_CONTEXT, MissingRegistryTableMap::TABLE_NAME, $record->id, $old, $new);
		}
	}

	/**
	 * @throws MissingRegistryException
	 */
	public function delete(int $id): void
	{
		$record = $this->getEditable($id);
		if (!$this->repository->softDelete($record->id)) {
			throw MissingRegistryException::notFound();
		}

		$this->changeAuditLogger->logDeleted(self::AUDIT_CONTEXT, MissingRegistryTableMap::TABLE_NAME, $record->id, self::AUDIT_LABEL);
	}

	/**
	 * Guard of the autosave endpoint: the record must be editable by the current user
	 * and only somebody who manages all records may hand a record to another person.
	 *
	 * @throws MissingRegistryException
	 */
	public function assertFieldChangeAllowed(int $id, string $field, mixed $rawValue): void
	{
		$record = $this->getEditable($id);
		if ($field !== self::OWNER_FIELD) {
			return;
		}

		$requestedUserId = is_scalar($rawValue) && preg_match('/^\d{1,9}$/', (string) $rawValue) === 1 ? (int) $rawValue : null;
		if (
			$requestedUserId === null
			|| !$this->policy->canManageAll()
			|| $this->policy->resolveTargetUserId($requestedUserId, $record) !== $requestedUserId
		) {
			throw MissingRegistryException::accessDenied();
		}
	}

	private function assertCanView(): void
	{
		if (!$this->policy->canView()) {
			throw MissingRegistryException::accessDenied();
		}
	}

	private function resolveUserId(MissingRegistryInput $input, ?MissingRegistryRecord $record, bool $requireOwner): int
	{
		$userId = $this->policy->resolveTargetUserId($input->userId, $record);
		if ($userId === null) {
			throw MissingRegistryException::accessDenied();
		}
		if ($requireOwner && $userId === 0) {
			throw MissingRegistryException::invalid('Vyberte, koho sa neprítomnosť týka.');
		}

		return $userId;
	}

	private function normalize(MissingRegistryInput $input, bool $requireComplete): MissingRegistryInput
	{
		$dateFrom = $input->dateFrom;
		$dateTo = $input->dateTo;
		$notice = trim($input->notice);

		if (mb_strlen($notice) > self::MAX_NOTICE_LENGTH) {
			throw MissingRegistryException::invalid('Poznámka môže mať najviac ' . self::MAX_NOTICE_LENGTH . ' znakov.');
		}

		if ($requireComplete) {
			if ($dateFrom === null) {
				throw MissingRegistryException::invalid('Zadajte dátum „Od“.');
			}
			if ($input->types === []) {
				throw MissingRegistryException::invalid('Vyberte aspoň jeden typ.');
			}

			$dateTo ??= $dateFrom;
			if ($dateTo < $dateFrom) {
				throw MissingRegistryException::invalid('Dátum „Do“ nemôže byť pred dátumom „Od“.');
			}
			if ((int) $dateFrom->diff($dateTo)->days >= self::MAX_RANGE_DAYS) {
				throw MissingRegistryException::invalid('Záznam môže trvať najviac ' . self::MAX_RANGE_DAYS . ' dní.');
			}
		}

		foreach ([$dateFrom, $dateTo] as $date) {
			if ($date !== null && !$this->isSupportedYear($date)) {
				throw MissingRegistryException::invalid('Dátum je mimo povoleného rozsahu rokov ' . self::MIN_YEAR . '–' . self::MAX_YEAR . '.');
			}
		}

		return new MissingRegistryInput($input->userId, $dateFrom, $dateTo, $input->types, $notice);
	}

	private function isSupportedYear(DateTimeImmutable $date): bool
	{
		$year = (int) $date->format('Y');

		return $year >= self::MIN_YEAR && $year <= self::MAX_YEAR;
	}

	private function describe(int $userId, MissingRegistryInput $input): string
	{
		$dates = array_unique(array_filter([$input->dateFrom?->format('d.m.Y'), $input->dateTo?->format('d.m.Y')]));
		$types = array_map(static fn (MissingType $type): string => $type->label(), $input->types);

		return implode(' · ', array_filter([
			'používateľ ' . $userId,
			implode('–', $dates),
			implode(' + ', $types),
			mb_substr($input->notice, 0, self::AUDIT_NOTICE_LENGTH),
		], static fn (string $part): bool => $part !== ''));
	}
}
