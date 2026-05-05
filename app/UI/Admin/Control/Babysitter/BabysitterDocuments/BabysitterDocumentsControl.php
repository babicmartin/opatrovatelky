<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Babysitter\BabysitterDocuments;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Enum\Acl\Resource;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Repository\FileRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Application\Responses\FileResponse;
use Nette\Http\FileUpload;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;
use Nette\Utils\Strings;

class BabysitterDocumentsControl extends Control
{
	private const int MAX_FILE_SIZE = 20_971_520;
	private const array ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docs', 'docx'];
	private const array ALLOWED_CONTENT_TYPES = [
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/octet-stream',
	];

	private int $babysitterId = 0;

	private string $dir = 'babysitters';

	private string $title = 'Dokumenty';

	public function __construct(
		private readonly FileRepository $fileRepository,
		private readonly BaseFormFactory $baseFormFactory,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
		private readonly User $user,
	) {
	}

	public function setContext(int $babysitterId): static
	{
		$this->babysitterId = $babysitterId;

		return $this;
	}

	public function render(): void
	{
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/BabysitterDocumentsControl.latte');
		$template->title = $this->title;
		$template->documents = $this->fileRepository->findDocuments($this->dir, $this->babysitterId);
		$template->statusOptions = $this->fileRepository->findStatusOptions();
		$template->typeImageBasePath = $this->storageDirProvider->getDocumentTypeImages();
		$template->canManageBabysitter = $this->user->isAllowed(Resource::BABYSITTER->value);
		$template->maxFileSize = self::MAX_FILE_SIZE;
		$template->render();
	}

	public function handleDownload(int $id): void
	{
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		$document = $this->fileRepository->findDocument($this->dir, $this->babysitterId, $id);
		if ($document === null) {
			$this->getPresenter()->error('Dokument neexistuje.', 404);
		}

		$path = $this->getPrivateDocumentDir() . '/' . $document['name'];
		if (!is_file($path)) {
			$this->getPresenter()->error('Súbor neexistuje.', 404);
		}

		$this->getPresenter()->sendResponse(new FileResponse($path, (string) $document['name']));
	}

	public function handleDelete(int $id): void
	{
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
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
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		return new Multiplier(function (string $id): Form {
			$document = $this->fileRepository->findDocument($this->dir, $this->babysitterId, (int) $id);
			if ($document === null) {
				$this->getPresenter()->error('Dokument neexistuje.', 404);
			}

			$form = $this->baseFormFactory->create();
			$form->getElementPrototype()->setAttribute('class', 'babysitter-document-form js-autosave-form');
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
				if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
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
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		$form = $this->baseFormFactory->create();
		$form->addUpload('fileAll')
			->setHtmlAttribute('id', 'fileAll')
			->setHtmlAttribute('data-max-size', (string) self::MAX_FILE_SIZE)
			->setHtmlAttribute('data-allowed-extensions', implode(',', self::ALLOWED_EXTENSIONS));
		$form->addSubmit('upload', '')
			->setHtmlAttribute('class', 'd-none js-upload-submit');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
				$this->getPresenter()->error('Prístup zamietnutý', 403);
			}

			/** @var FileUpload $upload */
			$upload = $values->fileAll;
			if (!$upload->isOk() || !$upload->hasFile()) {
				$this->redirect('this');
			}

			$extension = strtolower(pathinfo($upload->getSanitizedName(), PATHINFO_EXTENSION));
			if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
				$form->addError('Povolené sú iba tieto koncovky: ' . implode(', ', self::ALLOWED_EXTENSIONS));
				return;
			}

			if ($upload->getSize() > self::MAX_FILE_SIZE) {
				$form->addError('Maximálna veľkosť súboru je 20 MB.');
				return;
			}

			$contentType = (string) $upload->getContentType();
			if ($contentType !== '' && !in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
				$form->addError('Nepovolený typ súboru.');
				return;
			}

			$type = $extension === 'doc' || $extension === 'docx' ? 'docs' : $extension;
			$name = Strings::webalize(pathinfo($upload->getSanitizedName(), PATHINFO_FILENAME));
			$fileName = ($name !== '' ? $name : 'document') . '-' . Random::generate(8) . '.' . $extension;
			$targetDir = $this->getPrivateDocumentDir();

			FileSystem::createDir($targetDir);
			$upload->move($targetDir . '/' . $fileName);
			$this->fileRepository->insertDocument($this->dir, $this->babysitterId, $fileName, $type, (int) $this->user->getId());
			$this->redirect('this');
		};

		return $form;
	}

	private function getPrivateDocumentDir(): string
	{
		return $this->directoryProvider->getRootDir() . '/' . $this->storageDirProvider->getPrivateDocuments() . '/' . $this->dir . '/' . $this->babysitterId;
	}
}
