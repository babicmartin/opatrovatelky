<?php declare(strict_types=1);

namespace App\UI\Admin\Babysitter;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterAddress\BabysitterAddressForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterEducation\BabysitterEducationForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterMain\BabysitterMainForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterPdf\BabysitterPdfForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterProfile\BabysitterProfileForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterWorkProfile\BabysitterWorkProfileForm;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Repository\BabysitterRepository;
use App\Model\Utils\Validator\ImageValidator;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Babysitter\BabysitterDocuments\BabysitterDocumentsControl;
use App\UI\Admin\Control\Babysitter\BabysitterDocuments\BabysitterDocumentsControlFactory;
use App\UI\Admin\Control\Babysitter\BabysitterList\BabysitterListPresenterTrait;
use App\UI\Admin\Control\Filter\AlphabetFilter\AlphabetFilterPresenterTrait;
use App\UI\Admin\Form\Babysitter\BabysitterAddress\BabysitterAddressFormFactory;
use App\UI\Admin\Form\Babysitter\BabysitterEducation\BabysitterEducationFormFactory;
use App\UI\Admin\Form\Babysitter\BabysitterMain\BabysitterMainFormFactory;
use App\UI\Admin\Form\Babysitter\BabysitterPdf\BabysitterPdfFormFactory;
use App\UI\Admin\Form\Babysitter\BabysitterProfile\BabysitterProfileFormFactory;
use App\UI\Admin\Form\Babysitter\BabysitterWorkProfile\BabysitterWorkProfileFormFactory;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;

class BabysitterPresenter extends AdminPresenter
{
	use AlphabetFilterPresenterTrait;
	use BabysitterListPresenterTrait;

	private int $babysitterId = 0;

	private string $activeTab = 'main';

	/** @var array<string, mixed>|null */
	private ?array $babysitter = null;

	public function __construct(
		private readonly BabysitterRepository $babysitterRepository,
		private readonly BabysitterMainFormFactory $babysitterMainFormFactory,
		private readonly BabysitterAddressFormFactory $babysitterAddressFormFactory,
		private readonly BabysitterEducationFormFactory $babysitterEducationFormFactory,
		private readonly BabysitterProfileFormFactory $babysitterProfileFormFactory,
		private readonly BabysitterPdfFormFactory $babysitterPdfFormFactory,
		private readonly BabysitterWorkProfileFormFactory $babysitterWorkProfileFormFactory,
		private readonly BabysitterDocumentsControlFactory $babysitterDocumentsControlFactory,
		private readonly BaseFormFactory $baseFormFactory,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
		private readonly ImageValidator $imageValidator,
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

	public function actionUpdate(
		int $id,
		?string $tab = null,
		?int $address = null,
		?int $education = null,
		?int $profil = null,
		?int $documents = null,
		?int $pdf = null,
	): void
	{
		if (!$this->getUser()->isAllowed(Resource::BABYSITTER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$workProfileRaw = $this->getHttpRequest()->getQuery('work-profile');
		$workProfile = is_string($workProfileRaw) || is_int($workProfileRaw) ? (int) $workProfileRaw : null;

		$this->babysitterId = $id;
		$this->babysitter = $this->babysitterRepository->findUpdateRow($id);
		if ($this->babysitter === null) {
			$this->error('Opatrovateľka neexistuje.', 404);
		}

		$this->activeTab = $this->normalizeTab($tab, $address, $education, $profil, $workProfile, $documents, $pdf);

		$this->template->id = $id;
		$this->template->babysitter = $this->babysitter;
		$this->template->activeTab = $this->activeTab;
		$this->template->pageTitleText = (int) $this->babysitter['type'] === 1 ? 'Opatrovateľka' : 'Pracovník';
		$this->template->canManageBabysitter = $this->getUser()->isAllowed(Resource::BABYSITTER->value);
		$this->template->canOpenFamily = $this->getUser()->isAllowed(Resource::FAMILY->value);
		$this->template->canOpenTurnus = $this->getUser()->isAllowed(Resource::TURNUS->value);
		$this->template->turnusRows = $this->activeTab === 'main' ? $this->babysitterRepository->findTurnusRowsForBabysitter($id) : [];
		$this->template->pdfPath = 'export/opatrovatelky/pdf/' . $id . '.pdf';
	}

	public function handleCreate(): void
	{
		if (!$this->getUser()->isAllowed(Resource::BABYSITTER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->babysitterRepository->createEmptyBabysitter();
		$this->redirect('update', $id);
	}

	public function handleCreateTurnus(int $babysitterId): void
	{
		if (!$this->getUser()->isAllowed(Resource::BABYSITTER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->babysitterRepository->createTurnusForBabysitter($babysitterId, (int) $this->getUser()->getId());
		$this->redirect(':Admin:Turnus:update', $id);
	}

	protected function createComponentBabysitterMainForm(): Form
	{
		$babysitter = $this->getBabysitter();

		return $this->babysitterMainFormFactory->create(
			$babysitter,
			$this->babysitterRepository->findTypeSelectOptions(),
			$this->babysitterRepository->findAgencySelectOptions(),
			$this->babysitterRepository->findWorkingStatusSelectOptions(),
			$this->babysitterRepository->findStatusSelectOptions(),
			$this->babysitterRepository->findUserSelectOptions(),
			$this->babysitterRepository->findBlacklistSelectOptions(),
			$this->babysitterMainFormSucceeded(...),
		);
	}

	protected function createComponentBabysitterAddressForm(): Form
	{
		return $this->babysitterAddressFormFactory->create(
			$this->getBabysitter(),
			$this->babysitterRepository->findGenderSelectOptions(),
			$this->babysitterRepository->findCountrySelectOptions(),
			$this->babysitterAddressFormSucceeded(...),
		);
	}

	protected function createComponentBabysitterEducationForm(): Form
	{
		return $this->babysitterEducationFormFactory->create(
			$this->getBabysitter(),
			$this->babysitterRepository->findEducationSelectOptions(),
			$this->babysitterRepository->findDrivingLicenceSelectOptions(),
			$this->babysitterRepository->findYesNoSelectOptions(),
			$this->babysitterRepository->findLanguageSelectOptions(),
			$this->babysitterEducationFormSucceeded(...),
		);
	}

	protected function createComponentBabysitterProfileForm(): Form
	{
		return $this->babysitterProfileFormFactory->create(
			$babysitter = $this->getBabysitter(),
			$this->babysitterRepository->findSmokerSelectOptions(),
			$this->babysitterRepository->findYesNoSelectOptions(),
			$this->babysitterRepository->findAccommodationSelectOptions(),
			$this->babysitterRepository->findDiseaseSelectOptions(),
			$this->babysitterRepository->findDiseaseIds((int) $babysitter['id']),
			$this->babysitterProfileFormSucceeded(...),
		);
	}

	protected function createComponentBabysitterPdfForm(): Form
	{
		return $this->babysitterPdfFormFactory->create(
			$this->getBabysitter(),
			$this->babysitterRepository->findYesNoSelectOptions(),
			$this->babysitterPdfFormSucceeded(...),
		);
	}

	protected function createComponentBabysitterWorkProfileForm(): Form
	{
		$babysitter = $this->getBabysitter();

		return $this->babysitterWorkProfileFormFactory->create(
			$babysitter,
			$this->babysitterRepository->findWorkPositionSelectOptions(),
			$this->babysitterRepository->findQualificationIds((int) $babysitter['id']),
			$this->babysitterRepository->findPreferenceIds((int) $babysitter['id']),
			$this->babysitterWorkProfileFormSucceeded(...),
		);
	}

	protected function createComponentBabysitterDocuments(): BabysitterDocumentsControl
	{
		return $this->babysitterDocumentsControlFactory->create()
			->setContext($this->babysitterId);
	}

	protected function createComponentBabysitterImageForm(): Form
	{
		$this->assertCanManage();

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('enctype', 'multipart/form-data');
		$form->addUpload('image', 'Obrázok')
			->setRequired('Vyberte obrázok.')
			->addRule(Form::Image, 'Iba obrázky typu .png, .jpg');
		$form->addSubmit('save', 'Nahrať')
			->setHtmlAttribute('class', 'btn btn-success btn-sm');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			$this->assertCanManage();

			$upload = $values->image;
			if (!$upload instanceof FileUpload || !$upload->isOk()) {
				$form->addError('Obrázok sa nepodarilo nahrať.');
				return;
			}

			$extension = strtolower(pathinfo($upload->getName(), PATHINFO_EXTENSION));
			if (!in_array($extension, ['jpg', 'jpeg', 'png'], true) || !$this->imageValidator->isImage($upload->getTemporaryFile())) {
				$form->addError('Iba obrázky typu .png, .jpg');
				return;
			}

			$fileName = 'babysitter-' . $this->babysitterId . '-' . Random::generate(10, '0-9a-z') . '.' . $extension;
			$directory = $this->directoryProvider->getRootDir() . '/www/' . $this->storageDirProvider->getUserImages();
			FileSystem::createDir($directory);
			$upload->move($directory . '/' . $fileName);

			$this->babysitterRepository->updateImage($this->babysitterId, $fileName);
			$this->redirect('this');
		};

		return $form;
	}

	private function babysitterMainFormSucceeded(BabysitterMainForm $form): void
	{
		$this->assertCanManage();
		$this->babysitterRepository->updateMainFromForm($form);
		$this->finishAutosave();
	}

	private function babysitterAddressFormSucceeded(BabysitterAddressForm $form): void
	{
		$this->assertCanManage();
		$this->babysitterRepository->updateAddressFromForm($form);
		$this->finishAutosave();
	}

	private function babysitterEducationFormSucceeded(BabysitterEducationForm $form): void
	{
		$this->assertCanManage();
		$this->babysitterRepository->updateEducationFromForm($form);
		$this->finishAutosave();
	}

	private function babysitterProfileFormSucceeded(BabysitterProfileForm $form): void
	{
		$this->assertCanManage();
		$this->babysitterRepository->updateProfileFromForm($form);
		$this->finishAutosave();
	}

	private function babysitterPdfFormSucceeded(BabysitterPdfForm $form): void
	{
		$this->assertCanManage();
		$this->babysitterRepository->updatePdfFromForm($form);
		$this->finishAutosave();
	}

	private function babysitterWorkProfileFormSucceeded(BabysitterWorkProfileForm $form): void
	{
		$this->assertCanManage();
		$this->babysitterRepository->updateWorkProfileFromForm($form);
		$this->finishAutosave();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getBabysitter(): array
	{
		if ($this->babysitter === null) {
			$this->babysitter = $this->babysitterRepository->findUpdateRow($this->babysitterId);
			if ($this->babysitter === null) {
				$this->error('Opatrovateľka neexistuje.', 404);
			}
		}

		return $this->babysitter;
	}

	private function assertCanManage(): void
	{
		if (!$this->getUser()->isAllowed(Resource::BABYSITTER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}
	}

	private function finishAutosave(): void
	{
		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->redirect('this');
	}

	private function normalizeTab(?string $tab, ?int $address, ?int $education, ?int $profil, ?int $workProfile, ?int $documents, ?int $pdf): string
	{
		if ($tab === null) {
			if ($address === 1) {
				return 'info';
			}
			if ($education === 1) {
				return 'education';
			}
			if ($profil === 1) {
				return 'profil';
			}
			if ($workProfile === 1) {
				return 'work-profile';
			}
			if ($documents === 1) {
				return 'documents';
			}
			if ($pdf === 1) {
				return 'pdf';
			}

			return 'main';
		}

		return in_array($tab, ['main', 'info', 'education', 'profil', 'work-profile', 'documents', 'pdf'], true) ? $tab : 'main';
	}
}
