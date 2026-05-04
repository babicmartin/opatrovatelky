<?php declare(strict_types=1);

namespace App\UI\Admin\Country;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Enum\Acl\Resource;
use App\Model\Repository\CountryRepository;
use App\Model\Utils\Validator\ImageValidator;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Form\Country\CountryUpdate\CountryUpdateFormFactory;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;

final class CountryPresenter extends AdminPresenter
{
	/** @var array{id:int,name:string,german:string,image:string,active:int}|null */
	private ?array $country = null;

	public function __construct(
		private readonly CountryRepository $countryRepository,
		private readonly CountryUpdateFormFactory $countryUpdateFormFactory,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
		private readonly ImageValidator $imageValidator,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::COUNTRY->value;
	}

	public function actionDefault(): void
	{
		$this->template->countries = $this->countryRepository->findActiveRows();
	}

	public function actionUpdate(int $id): void
	{
		$country = $this->countryRepository->findRowById($id);
		if ($country === null) {
			$this->error('Krajina neexistuje.', 404);
		}

		$this->country = $country;
		$this->template->country = $country;
		$this->template->imagePath = $country['image'] !== ''
			? $this->storageDirProvider->getCountryImages() . '/' . $country['image']
			: null;
	}

	public function handleCreate(): void
	{
		if (!$this->getUser()->isAllowed(Resource::COUNTRY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->countryRepository->createEmpty();
		$this->redirect('update', $id);
	}

	protected function createComponentCountryUpdateForm(): Form
	{
		return $this->countryUpdateFormFactory->create(
			$this->getCountry(),
			$this->countryUpdateFormSucceeded(...),
		);
	}

	protected function createComponentCountryImageUpdateForm(): Form
	{
		return $this->countryUpdateFormFactory->createImageForm(
			$this->countryImageUpdateFormSucceeded(...),
		);
	}

	/**
	 * @param array{name:mixed,german:mixed} $data
	 */
	private function countryUpdateFormSucceeded(int $id, array $data): void
	{
		if (!$this->getUser()->isAllowed(Resource::COUNTRY->value) || $id !== $this->getCountry()['id']) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->countryRepository->updateTextFields($id, $data);

		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->flashMessage('Krajina bola uložená.', 'success');
		$this->redirect('this');
	}

	private function countryImageUpdateFormSucceeded(Form $form, ArrayHash $values): void
	{
		if (!$this->getUser()->isAllowed(Resource::COUNTRY->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

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

		$country = $this->getCountry();
		$fileName = 'country-' . $country['id'] . '-' . Random::generate(10, '0-9a-z') . '.' . $extension;
		$directory = $this->directoryProvider->getRootDir() . '/www/' . $this->storageDirProvider->getCountryImages();
		FileSystem::createDir($directory);
		$upload->move($directory . '/' . $fileName);

		$this->countryRepository->updateImage($country['id'], $fileName);
		$this->flashMessage('Obrázok bol nahraný.', 'success');
		$this->redirect('this');
	}

	/**
	 * @return array{id:int,name:string,german:string,image:string,active:int}
	 */
	private function getCountry(): array
	{
		if ($this->country === null) {
			$id = $this->getParameter('id');
			$this->country = is_numeric($id) ? $this->countryRepository->findRowById((int) $id) : null;
		}

		if ($this->country === null) {
			$this->error('Krajina neexistuje.', 404);
		}

		return $this->country;
	}
}
