<?php declare(strict_types=1);

namespace App\UI\Admin\MissingRegistry;

use App\Model\Enum\Acl\Resource;
use App\Model\Service\MissingRegistry\MissingRegistryException;
use App\Model\Service\MissingRegistry\MissingRegistryWriteService;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\MissingRegistry\MissingCalendar\MissingCalendarPresenterTrait;
use App\UI\Admin\Control\MissingRegistry\MissingRegistryList\MissingRegistryListPresenterTrait;

class MissingRegistryPresenter extends AdminPresenter
{
	use MissingCalendarPresenterTrait;
	use MissingRegistryListPresenterTrait;

	public function __construct(
		private readonly MissingRegistryWriteService $missingRegistryWriteService,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::MISSING->value;
	}

	public function actionDefault(): void
	{
	}

	/**
	 * Team calendar of one month; without parameters the current month.
	 */
	public function actionCalendar(?int $year = null, ?int $month = null): void
	{
		$this->prepareMissingCalendar();
	}

	/**
	 * Autosave of a registry row writes by the ID sent in the request, so the record
	 * and the right to hand it to another person are checked here before anything is saved.
	 */
	protected function validateAutosavePartialRequest(string $context, int $entityId): void
	{
		parent::validateAutosavePartialRequest($context, $entityId);

		if ($context !== MissingRegistryWriteService::AUDIT_CONTEXT) {
			return;
		}

		$request = $this->getHttpRequest();
		$field = $request->getPost('__autosave_field');

		try {
			$this->missingRegistryWriteService->assertFieldChangeAllowed(
				$entityId,
				is_string($field) ? $field : '',
				$request->getPost('__autosave_value'),
			);
		} catch (MissingRegistryException $e) {
			$this->sendAutosavePartialError($e->getCode(), $e->getMessage());
		}
	}
}
