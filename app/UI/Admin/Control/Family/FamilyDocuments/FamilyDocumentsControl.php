<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Family\FamilyDocuments;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Enum\Acl\Resource;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Repository\FileRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Http\FileUpload;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;
use Nette\Utils\Strings;

class FamilyDocumentsControl extends Control
{
	private int $familyId = 0;

	private string $dir = '';

	private string $title = '';

	public function __construct(
		private readonly FileRepository $fileRepository,
		private readonly BaseFormFactory $baseFormFactory,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
		private readonly User $user,
	) {
	}

	public function setContext(int $familyId, string $dir, string $title): static
	{
		$this->familyId = $familyId;
		$this->dir = $dir;
		$this->title = $title;

		return $this;
	}

	public function render(): void
	{
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/FamilyDocumentsControl.latte');
		$template->title = $this->title;
		$template->documents = $this->fileRepository->findDocuments($this->dir, $this->familyId);
		$template->statusOptions = $this->fileRepository->findStatusOptions();
		$template->documentBasePath = $this->storageDirProvider->getDocuments() . '/' . $this->dir . '/' . $this->familyId;
		$template->typeImageBasePath = $this->storageDirProvider->getDocumentTypeImages();
		$template->canManageFamily = $this->user->isAllowed(Resource::FAMILY->value);
		$template->render();
	}

	public function handleDelete(int $id): void
	{
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		$this->fileRepository->softDelete($id);
		$this->redirect('this');
	}

	/**
	 * @return Multiplier<Form>
	 */
	protected function createComponentDocumentForm(): Multiplier
	{
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		return new Multiplier(function (string $id): Form {
			$document = $this->findDocument((int) $id);
			if ($document === null) {
				$this->getPresenter()->error('Dokument neexistuje.', 404);
			}

			$form = $this->baseFormFactory->create();
			$form->getElementPrototype()->setAttribute('class', 'family-document-form js-autosave-form');
			$form->addHidden('id', (string) $document['id']);
			$form->addText('notice', $document['upload'])
				->setDefaultValue((string) $document['notice'])
				->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
			$form->addText('validFrom', 'Platnosť od')
				->setDefaultValue((string) $document['validFrom'])
				->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
				->setHtmlAttribute('autocomplete', 'off');
			$form->addText('validTo', 'Platnosť do')
				->setDefaultValue((string) $document['validTo'])
				->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
				->setHtmlAttribute('autocomplete', 'off');
			$form->addSelect('status', 'Status', $this->fileRepository->findStatusOptions())
				->setDefaultValue((int) $document['status'])
				->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
			$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

			$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
				if (!$this->user->isAllowed(Resource::FAMILY->value)) {
					$this->getPresenter()->error('Prístup zamietnutý', 403);
				}

				$this->fileRepository->updateDocument((int) $values->id, [
					'notice' => (string) $values->notice,
					'validFrom' => (string) $values->validFrom,
					'validTo' => (string) $values->validTo,
					'status' => (int) $values->status,
				]);

				if ($this->getPresenter()->isAjax()) {
					$this->getPresenter()->sendJson(['success' => true]);
				}

				$this->redirect('this');
			};

			return $form;
		});
	}

	protected function createComponentUploadForm(): Form
	{
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		$form = $this->baseFormFactory->create();
		$form->addUpload('fileAll')
			->setHtmlAttribute('id', 'fileAll');
		$form->addSubmit('upload', 'Nahrať')
			->setHtmlAttribute('class', 'btn btn-success btn-sm');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			if (!$this->user->isAllowed(Resource::FAMILY->value)) {
				$this->getPresenter()->error('Prístup zamietnutý', 403);
			}

			/** @var FileUpload $upload */
			$upload = $values->fileAll;
			if (!$upload->isOk() || !$upload->hasFile()) {
				$this->redirect('this');
			}

			$extension = strtolower(pathinfo($upload->getSanitizedName(), PATHINFO_EXTENSION));
			$type = $extension !== '' ? $extension : 'file';
			$name = Strings::webalize(pathinfo($upload->getSanitizedName(), PATHINFO_FILENAME));
			if ($name === '') {
				$name = 'document';
			}
			$fileName = $name . '-' . Random::generate(8) . ($extension !== '' ? '.' . $extension : '');
			$targetDir = $this->directoryProvider->getRootDir() . '/www/' . $this->storageDirProvider->getDocuments() . '/' . $this->dir . '/' . $this->familyId;

			FileSystem::createDir($targetDir);
			$upload->move($targetDir . '/' . $fileName);
			$this->fileRepository->insertDocument($this->dir, $this->familyId, $fileName, $type, (int) $this->user->getId());
			$this->redirect('this');
		};

		return $form;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function findDocument(int $id): ?array
	{
		foreach ($this->fileRepository->findDocuments($this->dir, $this->familyId) as $document) {
			if ((int) $document['id'] === $id) {
				return $document;
			}
		}

		return null;
	}
}
