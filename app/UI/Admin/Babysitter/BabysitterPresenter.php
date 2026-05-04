<?php declare(strict_types=1);

namespace App\UI\Admin\Babysitter;

use App\Model\Enum\Acl\Resource;
use App\Model\Repository\BabysitterRepository;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Babysitter\BabysitterList\BabysitterListPresenterTrait;
use App\UI\Admin\Control\Filter\AlphabetFilter\AlphabetFilterPresenterTrait;

class BabysitterPresenter extends AdminPresenter
{
	use AlphabetFilterPresenterTrait;
	use BabysitterListPresenterTrait;

	public function __construct(
		private readonly BabysitterRepository $babysitterRepository,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::BABYSITTER->value;
	}

	public function actionDefault(
		?int $page = null,
		?int $country = null,
		?int $language = null,
		?int $gender = null,
		?int $driver = null,
		?int $smoker = null,
		?int $agency = null,
		?int $status = null,
	): void
	{
		$query = $this->getHttpRequest()->getQuery();
		$firstLetterRaw = $query['first-letter'] ?? null;
		$workingStatusRaw = $query['working-status'] ?? null;

		$firstLetter = is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null;
		$workingStatusId = is_string($workingStatusRaw) && $workingStatusRaw !== '' ? (int) $workingStatusRaw : null;

		$this->template->page = $page;
		$this->template->country = $country;
		$this->template->language = $language;
		$this->template->workingStatus = $workingStatusId;
		$this->template->gender = $gender;
		$this->template->driver = $driver;
		$this->template->smoker = $smoker;
		$this->template->agency = $agency;
		$this->template->status = $status;
		$this->template->firstLetter = $firstLetter;

		$this->template->countries = $this->babysitterRepository->findCountryOptions();
		$this->template->languages = $this->babysitterRepository->findLanguageOptions();
		$this->template->workingStatuses = $this->babysitterRepository->findWorkingStatusOptions();
		$this->template->genders = $this->babysitterRepository->findGenderOptions();
		$this->template->yesNoOptions = $this->babysitterRepository->findYesNoOptions();
		$this->template->agencies = $this->babysitterRepository->findAgencyOptions();
		$this->template->statuses = $this->babysitterRepository->findStatusOptions();

		$this->template->canManageBabysitter = $this->getUser()->isAllowed(Resource::BABYSITTER->value);
	}

	public function actionUpdate(int $id): void
	{
		if (!$this->getUser()->isAllowed(Resource::BABYSITTER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->template->id = $id;
	}

	public function handleCreate(): void
	{
		if (!$this->getUser()->isAllowed(Resource::BABYSITTER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->babysitterRepository->createEmptyBabysitter();
		$this->redirect('update', $id);
	}
}
